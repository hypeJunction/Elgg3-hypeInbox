<?php

namespace hypeJunction\Inbox;

use Elgg\IntegrationTestCase;

/**
 * Integration coverage that the migrated elgg-plugin.php routes and the
 * Bootstrap::init() event wiring actually register on a booted Elgg 7 site.
 */
class RegistrationBehaviorTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'hypeinbox';
	}

	/**
	 * c92ea9a: the {username?} segment of the search route is optional, so the
	 * route must generate for both the current user (no segment) and a named
	 * user. A required parameter would throw when generating without it.
	 */
	public function testSearchRouteResolvesWithAndWithoutUsername(): void {
		$without = elgg_generate_url('collection:object:messages:search');
		$this->assertIsString($without);
		$this->assertStringContainsString('/messages/search', $without);

		$with = elgg_generate_url('collection:object:messages:search', ['username' => 'alice']);
		$this->assertStringContainsString('/messages/search/alice', $with);
	}

	/**
	 * The compose/read/thread routes are registered by the manifest.
	 */
	public function testCoreMessageRoutesRegistered(): void {
		$this->assertStringContainsString(
			'/messages/thread/abc123',
			elgg_generate_url('collection:object:messages:thread', ['hash' => 'abc123'])
		);
		$this->assertStringContainsString(
			'/messages/read/42',
			elgg_generate_url('read:object:messages', ['guid' => 42])
		);
	}

	/**
	 * Bootstrap::init() wires the migrated static handlers (topbar/page menus,
	 * seeds:database, output:ajax, entity icon URL). hasHandler proves each one
	 * survived the 6.x hook->event rename and is bound at the right lifecycle.
	 */
	public function testMigratedEventHandlersRegistered(): void {
		$events = _elgg_services()->events;

		$this->assertTrue($events->hasHandler('register', 'menu:topbar'), 'topbar menu handler missing');
		$this->assertTrue($events->hasHandler('register', 'menu:page'), 'page menu handler missing');
		$this->assertTrue($events->hasHandler('register', 'menu:entity'), 'entity menu handler missing');
		$this->assertTrue($events->hasHandler('seeds', 'database'), 'seeds:database handler missing');
		$this->assertTrue($events->hasHandler('output', 'ajax'), 'output:ajax handler missing');
		$this->assertTrue($events->hasHandler('entity:icon:url', 'object'), 'entity icon url handler missing');
	}

	/**
	 * The serialize()->json settings upgrade is registered and reports two
	 * items (default_message_types + message_types).
	 */
	public function testSettingsMigrationUpgradeRegistered(): void {
		$batch = new \hypeJunction\Inbox\Upgrades\MigrateSettingsToJson();
		$this->assertInstanceOf(\Elgg\Upgrade\Batch::class, $batch);
		$this->assertSame(2, $batch->countItems());
	}
}
