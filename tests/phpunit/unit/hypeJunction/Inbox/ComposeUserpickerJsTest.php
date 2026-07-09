<?php

namespace hypeJunction\Inbox;

use Elgg\UnitTestCase;

/**
 * Regression guard for the /messages/add (compose) surface.
 *
 * The e2e "messages-and-notifications" flow failed on the compose page with an
 * uncaught `elgg.register_hook_handler is not a function` TypeError. The call
 * lived in the plugin-owned topbar module views/default/framework/inbox/popup.js,
 * which registered `elgg.register_hook_handler('getOptions', 'ui.popup', ...)`.
 *
 * `elgg.register_hook_handler` (and the rest of the legacy global pub/sub API)
 * was removed in the Elgg 6/7 JS runtime. Because the inbox topbar module loads
 * on EVERY authenticated page — including /messages/add — the thrown TypeError
 * aborts the module and takes the recipient userpicker down with it.
 *
 * The fix migrated popup.js -> popup.mjs and replaced the removed hook with a
 * jQuery `open` binding. These tests keep any of the removed globals from ever
 * re-entering the plugin's JS views.
 */
class ComposeUserpickerJsTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	/**
	 * Legacy global methods on the `elgg` object that were removed in the
	 * Elgg 6/7 JS runtime. Calling any of them throws a TypeError at module
	 * evaluation time (the exact compose-surface failure). This intentionally
	 * does NOT include still-supported members such as elgg.action,
	 * elgg.nullFunction or elgg.config, which the current modules rely on.
	 */
	private const REMOVED_JS_GLOBALS = [
		'register_hook_handler',
		'unregister_hook_handler',
		'trigger_hook',
		'register_event_handler',
		'unregister_event_handler',
		'trigger_event',
		'add_action',
	];

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

	/**
	 * @return string[] Absolute paths of every JS view module (.mjs and .js)
	 */
	private function jsViewFiles(): array {
		$base = $this->pluginRoot() . '/views';
		$out = [];
		if (!is_dir($base)) {
			return $out;
		}
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
		foreach ($it as $f) {
			$path = $f->getPathname();
			if (str_contains($path, '/vendor/') || str_contains($path, '/vendors/') || str_contains($path, '/node_modules/')) {
				continue;
			}
			if (str_ends_with($path, '.mjs') || str_ends_with($path, '.js')) {
				$out[] = $path;
			}
		}
		return $out;
	}

	public function testJsViewsCallNoRemovedElggGlobals(): void {
		$files = $this->jsViewFiles();
		$this->assertNotEmpty($files, 'Expected the plugin to ship JS view modules to scan');

		$alt = implode('|', array_map('preg_quote', self::REMOVED_JS_GLOBALS));
		// Match `elgg.<removed>(` and `elgg['<removed>'](` invocations.
		$re = '/\belgg\s*(?:\.\s*(' . $alt . ')|\[\s*[\'"](' . $alt . ')[\'"]\s*\])\s*\(/';

		$violations = [];
		foreach ($files as $file) {
			foreach (explode("\n", (string) file_get_contents($file)) as $n => $line) {
				if (preg_match($re, $line, $m)) {
					$symbol = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
					$violations[] = sprintf(
						'%s:%d — elgg.%s() was removed in the Elgg 6/7 JS runtime (throws "is not a function" and aborts the module on the compose surface)',
						basename($file),
						$n + 1,
						$symbol
					);
				}
			}
		}

		$this->assertSame([], $violations, "Removed legacy Elgg JS globals in view modules:\n" . implode("\n", $violations));
	}

	public function testPopupModuleReplacedRemovedHookWithOpenBinding(): void {
		$src = $this->read('views/default/framework/inbox/popup.mjs');

		$this->assertStringNotContainsString(
			'register_hook_handler',
			$src,
			'popup.mjs must not re-introduce the removed elgg.register_hook_handler call that broke the compose surface'
		);

		// The removed `getOptions/ui.popup` hook was replaced by a jQuery
		// binding on the popup `open` event; without it the inbox badge never
		// loads its unread list.
		$this->assertMatchesRegularExpression(
			'/\$\(\s*document\s*\)\s*\.\s*on\(\s*[\'"]open[\'"]\s*,\s*[\'"]\.elgg-inbox-popup[\'"]/',
			$src,
			'popup.mjs must bind the inbox popup via the jQuery `open` event (the replacement for the removed ui.popup getOptions hook)'
		);
	}

	public function testNoLegacyPopupJsTwinRemains(): void {
		$this->assertFileDoesNotExist(
			$this->pluginRoot() . '/views/default/framework/inbox/popup.js',
			'The legacy AMD popup.js (source of the removed elgg.register_hook_handler call) must stay deleted'
		);
	}

	public function testComposeFormWiresRecipientPickerToCoreGuidsInput(): void {
		$src = $this->read('views/default/forms/messages/send.php');

		// The compose recipient picker must ride on the core `input/guids`
		// field type (maintained in 7.x), not a removed/custom userpicker JS.
		$this->assertMatchesRegularExpression(
			'/[\'"]#type[\'"]\s*=>\s*[\'"]guids[\'"]/',
			$src,
			'send.php recipient field must use the core #type => guids userpicker'
		);
		$this->assertMatchesRegularExpression(
			'/[\'"]name[\'"]\s*=>\s*[\'"]recipients[\'"]/',
			$src,
			'send.php userpicker must post as the "recipients" field'
		);

		// The userpicker autocompletes against the plugin route; keep them wired.
		$this->assertMatchesRegularExpression(
			'/[\'"]source[\'"]\s*=>\s*elgg_generate_url\(\s*[\'"]autocomplete:inbox:guids[\'"]/',
			$src,
			'send.php userpicker must source suggestions from the autocomplete:inbox:guids route'
		);
	}
}
