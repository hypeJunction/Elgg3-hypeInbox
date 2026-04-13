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
 * Intentionally characterizes the current broken state of several camelCase
 * plugin-id callsites (autoloader, Bootstrap::activate, settings action) —
 * Elgg 4.x plugin ids are lowercase, so elgg_get_plugin_from_id('hypeInbox')
 * and elgg_get_plugin_setting(..., 'hypeInbox') both silently return null.
 * The tests assert that current behavior so fixing them surfaces as a test
 * update, not a silent semantics change.
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

	// --- camelCase plugin-id footguns (characterize broken-state) ---

	public function testCamelCaseIdLookupReturnsNull() {
		// autoloader.php line 18 + actions/settings/save.php both use
		// elgg_get_plugin_from_id('hypeInbox') (camelCase). Elgg 4.x plugin
		// ids are lowercase — the call silently returns null.
		$this->assertNull(elgg_get_plugin_from_id('hypeInbox'));
	}

	public function testCamelCaseSettingReadReturnsFalse() {
		// Bootstrap::activate stores default_message_types with a camelCase
		// plugin id. In Elgg 4.x the plugin-id lookup fails silently and
		// elgg_get_plugin_setting returns bool(false) — NOT null. That's
		// different from a lowercase lookup against a real plugin, which
		// returns null for an unset key. The two values are semantically
		// distinct (false = "no such plugin", null = "no such setting").
		$this->assertSame(false, elgg_get_plugin_setting('default_message_types', 'hypeInbox'));
	}

	public function testLowercaseSettingReadReturnsNullForUnsetKey() {
		// Control: confirm that lowercase lookups DO find the plugin and
		// return null for unset keys, proving the camelCase fallthrough is
		// a plugin-id resolution failure, not a generic missing-setting.
		$this->assertNull(elgg_get_plugin_setting('does_not_exist', 'hypeinbox'));
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

	// --- menu / hook wiring (Bootstrap::init) ---

	public function testPageOwnerHookHandlerWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('page_owner', $handlers);
	}

	public function testEntityUrlHookHandlerWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('entity:url', $handlers);
	}
}
