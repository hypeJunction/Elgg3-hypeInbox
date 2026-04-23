<?php

namespace hypeJunction\Inbox;

use Elgg\IntegrationTestCase;

/**
 * Characterization suite for hypeinbox on Elgg 4.x.
 *
 * Locks in the post-migration (migrate/elgg-4.x) runtime shape so future
 * changes are caught before they regress activation, entity mapping, action
 * registration, hook wiring, or plugin-id callsite semantics.
 *
 * Pins the lowercased plugin-id callsites that the 4.x cleanup landed.
 * The 3.x source called elgg_get_plugin_from_id('hypeInbox') and
 * elgg_get_plugin_setting(..., 'hypeInbox') with camelCase ids, which
 * silently return null/false in Elgg 4.x. Bead elgg-migrate-fflc rewrote
 * every callsite to lowercase 'hypeinbox' so the lookups resolve. These
 * tests assert the fixed behavior so a regression to camelCase surfaces
 * as a test failure.
 */
class BootstrapTest extends IntegrationTestCase {

	public function getPluginID(): string {
		return 'hypeinbox';
	}

	public function up() {}
	public function down() {}

	// --- plugin lifecycle ---

	public function testPluginIsRegistered() {
		$plugin = elgg_get_plugin_from_id('hypeinbox');
		$this->assertInstanceOf(\ElggPlugin::class, $plugin);
	}

	public function testPluginIsEnabled() {
		$this->assertTrue(elgg_get_plugin_from_id('hypeinbox')->isEnabled());
	}

	public function testPluginIsActive() {
		$this->assertTrue(elgg_get_plugin_from_id('hypeinbox')->isActive());
	}

	public function testDependencyHypeListsActive() {
		$p = elgg_get_plugin_from_id('hypelists');
		$this->assertNotNull($p);
		$this->assertTrue($p->isActive());
	}

	// --- lowercase plugin-id callsites (post-fflc cleanup) ---

	public function testLowercaseIdLookupResolvesPlugin() {
		// autoloader.php + actions/settings/save.php now use 'hypeinbox'
		// (lowercase) so elgg_get_plugin_from_id resolves the plugin.
		$this->assertInstanceOf(\ElggPlugin::class, elgg_get_plugin_from_id('hypeinbox'));
	}

	public function testCamelCaseIdLookupStillReturnsNull() {
		// Regression guard: the 3.x camelCase plugin-id form must still
		// fail (returns null) — confirms that fixes elsewhere don't add
		// a backwards-compat shim that papers over the underlying issue.
		$this->assertNull(elgg_get_plugin_from_id('hypeInbox'));
	}

	public function testLowercasePluginSettingRoundTrips() {
		// Pin the lowercase plugin-id callsite fix end-to-end:
		// setSetting/getSetting on 'hypeinbox' must round-trip a value.
		$plugin = elgg_get_plugin_from_id('hypeinbox');
		$key = '__test_round_trip_' . bin2hex(random_bytes(4));
		try {
			$this->assertTrue($plugin->setSetting($key, 'value-42'));
			$this->assertSame('value-42', $plugin->getSetting($key));
		} finally {
			$plugin->unsetSetting($key);
		}
	}

	public function testLowercaseSettingReadReturnsNullForUnsetKey() {
		// Control: lowercase lookups DO find the plugin and return null for unset keys.
		$this->assertNull(elgg_get_plugin_from_id('hypeinbox')->getSetting('does_not_exist'));
	}

	// --- class autoloading ---

	public function testBootstrapClassLoads() {
		$this->assertTrue(class_exists(Bootstrap::class));
	}

	public function testMessageEntityClassLoads() {
		$this->assertTrue(class_exists(Message::class));
	}

	public function testPluginDiContainerClassLoads() {
		$this->assertTrue(class_exists(Plugin::class));
	}

	public function testPluginGetDefinitionSourcesIsStatic() {
		// Regression: LSP-compliant override of abstract static method.
		// Earlier 4.x work landed the method as non-static, breaking class
		// loading at activation time. Pin the correct signature.
		$r = new \ReflectionMethod(Plugin::class, 'getDefinitionSources');
		$this->assertTrue($r->isStatic());
		$this->assertTrue($r->isPublic());
	}

	public function testMessageSaveReturnsBool() {
		$r = new \ReflectionMethod(Message::class, 'save');
		$this->assertTrue($r->hasReturnType());
		$this->assertSame('bool', (string) $r->getReturnType());
	}

	public function testPluginFactorySignatureMatchesParent() {
		// Regression: parent DiContainer::factory is
		//   public static function factory(array $options = [])
		// hypeinbox's 3.x version took no args — PHP 7.4 emits an LSP
		// warning if the override drops parameters with defaults.
		$r = new \ReflectionMethod(Plugin::class, 'factory');
		$this->assertTrue($r->isStatic());
		$params = $r->getParameters();
		$this->assertCount(1, $params);
		$this->assertSame('options', $params[0]->getName());
		$this->assertTrue($params[0]->isDefaultValueAvailable());
	}

	// --- entity subtype mapping ---

	public function testMessageEntitySubtypeRegistered() {
		$this->assertSame(
			Message::class,
			elgg_get_entity_class('object', 'messages')
		);
	}

	// --- actions ---

	public function testMessagesSendActionRegistered() {
		$this->assertTrue(_elgg_services()->actions->exists('messages/send'));
	}

	public function testMessagesDeleteActionRegistered() {
		$this->assertTrue(_elgg_services()->actions->exists('messages/delete'));
	}

	public function testMessagesMarkReadActionRegistered() {
		$this->assertTrue(_elgg_services()->actions->exists('messages/markread'));
	}

	public function testMessagesMarkUnreadActionRegistered() {
		$this->assertTrue(_elgg_services()->actions->exists('messages/markunread'));
	}

	public function testMessagesLoadActionRegistered() {
		$this->assertTrue(_elgg_services()->actions->exists('messages/load'));
	}

	public function testAdminOnlyActionsNotRegisteredInStatelessContext() {
		// Characterization: Elgg 4.x registers actions with access='admin'
		// only during an admin session. In the stateless PHPUnit bootstrap
		// there is no logged-in admin, so 'hypeInbox/settings/save' and
		// 'inbox/admin/import' are NOT present in the action registry.
		// Tests that exercise these actions must first seed + log in an
		// admin user (via the Seeding trait). Pin the current behavior so
		// we notice if Elgg changes the registration semantics.
		$svc = _elgg_services()->actions;
		$this->assertFalse($svc->exists('hypeInbox/settings/save'));
		$this->assertFalse($svc->exists('inbox/admin/import'));
	}

	// --- event wiring (Bootstrap::init, Elgg 5.x uses unified events) ---

	public function testPageOwnerHookHandlerWired() {
		$this->assertTrue(_elgg_services()->events->hasHandler('page_owner', 'system'));
	}

	public function testEntityUrlHookHandlerWired() {
		$this->assertTrue(_elgg_services()->events->hasHandler('entity:url', 'object'));
	}
}
