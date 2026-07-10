<?php

namespace hypeJunction\Inbox;

use Elgg\Event;
use Elgg\UnitTestCase;
use hypeJunction\Inbox\Upgrades\MigrateSettingsToJson;

/**
 * Pure-logic + source regression coverage for the hypeinbox 6.x -> 7.x
 * migration fixes. Every method here anchors a specific commit from the
 * migration so a future edit that reintroduces the removed API fails CI.
 *
 * No database: file reads, reflection, and mocked \Elgg\Event dispatch only.
 */
class MigrationFixesTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	private function pluginRoot(): string {
		$dir = __DIR__;
		for ($i = 0; $i < 8; $i++) {
			if (is_file($dir . '/elgg-plugin.php') && is_file($dir . '/composer.json')) {
				return $dir;
			}
			$dir = dirname($dir);
		}
		$this->fail('Could not locate plugin root from ' . __DIR__);
	}

	private function read(string $relative): string {
		$path = $this->pluginRoot() . '/' . ltrim($relative, '/');
		$this->assertFileExists($path);

		return (string) file_get_contents($path);
	}

	/** @return list<string> non-vendor *.php under a relative subdir */
	private function phpFiles(string $sub): array {
		$base = $this->pluginRoot() . '/' . trim($sub, '/');
		$out = [];
		if (!is_dir($base)) {
			return $out;
		}
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
		foreach ($it as $f) {
			$path = $f->getPathname();
			if (str_contains($path, '/vendor/') || str_contains($path, '/vendors/')) {
				continue;
			}
			if (str_ends_with($path, '.php')) {
				$out[] = $path;
			}
		}
		return $out;
	}

	private function eventReturning($value): Event {
		$event = $this->getMockBuilder(Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getValue')->willReturn($value);

		return $event;
	}

	/**
	 * d85ed1d: Elgg 7 removed the 'elgg/notify' ESM module. The bare specifier
	 * failed to resolve and aborted the whole admin module, so the import was
	 * renamed to 'elgg/system_messages'.
	 */
	public function testAdminMjsImportsSystemMessagesNotNotify(): void {
		$src = $this->read('views/default/framework/inbox/admin.mjs');

		$this->assertStringContainsString(
			"'elgg/system_messages'",
			$src,
			'admin.mjs must import the 7.x system_messages module'
		);
		$this->assertStringNotContainsString(
			"'elgg/notify'",
			$src,
			"admin.mjs still imports removed 'elgg/notify' (aborts the module on 7.x)"
		);
	}

	/**
	 * b7d7dc3: ES-module views must be .mjs so Elgg 7's importmap registers
	 * them; the legacy .js twins were removed.
	 */
	public function testEsModuleViewsAreMjsNotJs(): void {
		$root = $this->pluginRoot();

		foreach ([
			'views/default/framework/inbox/message.mjs',
			'views/default/framework/inbox/admin.mjs',
			'views/default/framework/inbox/popup.mjs',
			'views/default/framework/inbox/user.mjs',
			'views/default/input/inbox/message.mjs',
		] as $mjs) {
			$this->assertFileExists($root . '/' . $mjs, "ESM view missing: $mjs");
			$this->assertFileDoesNotExist(
				$root . '/' . substr($mjs, 0, -4) . '.js',
				"legacy AMD twin still present for $mjs"
			);
		}
	}

	/**
	 * b7d7dc3: ElggPlugin::getManifest() was removed in 7.x — the settings
	 * action now derives its name from getDisplayName().
	 */
	public function testSettingsSaveUsesGetDisplayNameNotGetManifest(): void {
		$src = $this->read('actions/settings/save.php');

		$this->assertStringContainsString('getDisplayName(', $src);
		$this->assertDoesNotMatchRegularExpression(
			'/->\s*getManifest\s*\(/',
			$src,
			'settings/save.php still calls removed ElggPlugin::getManifest()'
		);
	}

	/**
	 * b7d7dc3: the built-in __private message-type labels are registered
	 * statically in languages/en.php (they used to be added at runtime, which
	 * 500s on the admin page when the plugin setting is unset).
	 */
	public function testPrivateMessageTypeLabelsAreStaticInLanguages(): void {
		$src = $this->read('languages/en.php');

		$this->assertStringContainsString('object:message:__private:singular', $src);
		$this->assertStringContainsString('object:message:__private:plural', $src);
	}

	/**
	 * 250b888: composer autoload moved from psr-0 to psr-4 for Elgg 7.x.
	 */
	public function testComposerAutoloadIsPsr4NotPsr0(): void {
		$composer = json_decode($this->read('composer.json'), true);

		$this->assertIsArray($composer);
		$this->assertArrayHasKey('psr-4', $composer['autoload'] ?? []);
		$this->assertArrayNotHasKey('psr-0', $composer['autoload'] ?? []);
		$this->assertSame(
			'classes/hypeJunction/Inbox/',
			$composer['autoload']['psr-4']['hypeJunction\\Inbox\\'] ?? null
		);
	}

	/**
	 * 416c529: sanitize_string() was dropped long ago and fatals on 7.x. No
	 * class or action file may call it.
	 */
	public function testNoLegacySanitizeStringCalls(): void {
		$violations = [];
		foreach (array_merge($this->phpFiles('classes'), $this->phpFiles('actions')) as $file) {
			foreach (explode("\n", (string) file_get_contents($file)) as $n => $line) {
				if (preg_match('/(?<![\w>$:\\\\])sanitize_string\s*\(/', $line)) {
					$violations[] = basename($file) . ':' . ($n + 1);
				}
			}
		}
		$this->assertSame([], $violations, "Legacy sanitize_string() calls (removed):\n" . implode("\n", $violations));
	}

	/**
	 * a87c20a: the 6.x \Elgg\Hook was replaced by \Elgg\Event. Handler
	 * delegates must receive \Elgg\Event and no source may reference \Elgg\Hook.
	 */
	public function testHandlerSignaturesUseElggEvent(): void {
		foreach ([
			[Router::class, 'resolvePageOwner'],
			[Router::class, 'messageUrlHandler'],
			[Ajax::class, 'setUnreadMessagesCount'],
			[Config::class, 'filterUserTypes'],
		] as [$class, $method]) {
			$params = (new \ReflectionMethod($class, $method))->getParameters();
			$this->assertNotEmpty($params, "$class::$method has no parameters");
			$type = $params[0]->getType();
			$this->assertInstanceOf(\ReflectionNamedType::class, $type);
			$this->assertSame(
				Event::class,
				$type->getName(),
				"$class::$method must accept \\Elgg\\Event, got " . $type->getName()
			);
		}

		$violations = [];
		foreach ($this->phpFiles('classes') as $file) {
			if (preg_match('/\\\\?Elgg\\\\Hook\b/', (string) file_get_contents($file))) {
				$violations[] = basename($file);
			}
		}
		$this->assertSame([], $violations, "Removed \\Elgg\\Hook referenced in:\n" . implode("\n", $violations));
	}

	/**
	 * 93f3af1 + 35c1616: the Seeder is a real \Elgg\Database\Seeds\Seed subclass
	 * satisfying the 6.1 abstract contract (getType + getCountOptions), and
	 * addSeed appends itself to the \Elgg\Event value.
	 */
	public function testSeederExposesSeedContract(): void {
		$this->assertSame('Elgg\\Database\\Seeds\\Seed', get_parent_class(Seeder::class));
		$this->assertSame('messages', Seeder::getType());

		$count = new \ReflectionMethod(Seeder::class, 'getCountOptions');
		$this->assertFalse($count->isStatic());

		$addSeedParam = (new \ReflectionMethod(Seeder::class, 'addSeed'))->getParameters()[0] ?? null;
		$this->assertNotNull($addSeedParam);
		$this->assertSame(Event::class, $addSeedParam->getType()?->getName());

		$result = Seeder::addSeed($this->eventReturning(['Other\\Seed']));
		$this->assertContains(Seeder::class, $result);
		$this->assertContains('Other\\Seed', $result, 'addSeed must preserve existing seeds');
	}

	/**
	 * MigrateSettingsToJson is an asynchronous Upgrade\Batch (abstract since
	 * 6.x). Its item count and version are pure and must not touch the DB.
	 *
	 * needsIncrementOffset() MUST be true. Elgg\Upgrade\Loop::isCompleted() only
	 * ends a `false` batch when countItems() SHRINKS TO ZERO — and this one returns
	 * the constant 2. Asserting false here is what let the batch run forever: its
	 * progress counter passed 1.5 million iterations on bodyology and stalled every
	 * upgrade queued behind it, until Elgg deleted the classes of the ones waiting.
	 */
	public function testMigrateSettingsBatchContract(): void {
		$batch = new MigrateSettingsToJson();

		$this->assertInstanceOf(\Elgg\Upgrade\Batch::class, $batch);
		$this->assertSame(2, $batch->countItems());
		$this->assertSame(2026042301, $batch->getVersion());
		$this->assertTrue(
			$batch->needsIncrementOffset(),
			'a constant countItems() with needsIncrementOffset() === false never terminates'
		);
		$this->assertFalse($batch->shouldBeSkipped());
	}

	/**
	 * 5340eca: on 7.x the register/menu event value is a plain array. The
	 * handler must $menu[] = ...; and return the array — a MenuItems->add()
	 * fatals. Exercise the append path directly in the admin context.
	 */
	public function testAdminPageMenuAppendsItemAsArray(): void {
		elgg_push_context('admin');
		try {
			$result = Menus::setupAdminPageMenu($this->eventReturning([]));
		} finally {
			elgg_pop_context();
		}

		$this->assertIsArray($result);
		$names = array_map(static fn (\ElggMenuItem $i) => $i->getName(), $result);
		$this->assertContains('message_types', $names, 'admin page menu must append the message_types item');
	}
}
