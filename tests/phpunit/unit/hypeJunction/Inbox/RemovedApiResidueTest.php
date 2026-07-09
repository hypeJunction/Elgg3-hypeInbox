<?php

namespace hypeJunction\Inbox;

use Elgg\UnitTestCase;

/**
 * Plugin-specific regression guard for two live removed-API residues that the
 * 7.x migration must have eliminated but the generic MigrationRegressionTest
 * does not pin by name:
 *
 *   1. Config::registerLabels() still calls add_translation('en', [...]).
 *      add_translation() was REMOVED in Elgg 5.0. The runtime label wiring was
 *      supposed to be replaced by the static keys in languages/en.php
 *      (see MigrationFixesTest::testPrivateMessageTypeLabelsAreStaticInLanguages).
 *      The old runtime call is now dead-but-fatal: any code path that invokes
 *      registerLabels() on 7.x dies with "Call to undefined function
 *      add_translation()". The migration is not complete until that call is gone.
 *
 *   2. A root deactivate.php survives from the 2.x/3.x lifecycle and calls
 *      update_subtype('object', ...) — a function removed years ago and absent
 *      from the generic guard's removed-functions map. On Elgg 4.x+ a root
 *      deactivate.php is itself a forbidden bootstrap file: the plugin is
 *      REJECTED at activation, so the site never boots with hypeinbox enabled.
 *
 * Pure source/reflection reads — no DB. These assertions go GREEN only when the
 * dead add_translation() call is deleted from Config.php and deactivate.php is
 * removed from the plugin root.
 */
class RemovedApiResidueTest extends UnitTestCase {

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

	/** @return list<string> non-vendor, non-test *.php anywhere under the plugin root */
	private function sourcePhpFiles(): array {
		$root = $this->pluginRoot();
		$out = [];
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
		foreach ($it as $f) {
			$path = $f->getPathname();
			if (preg_match('#/(vendor|vendors|node_modules|bower_components|tests)/#', $path)) {
				continue;
			}
			if (str_ends_with($path, '.php')) {
				$out[] = $path;
			}
		}
		return $out;
	}

	/**
	 * Returns [file:line] hits of a bare global-function call $fn( in real code
	 * lines (comment / docblock lines are ignored so a migration note about the
	 * removed symbol is not mistaken for a live call).
	 *
	 * @return list<string>
	 */
	private function liveCallSites(string $fn): array {
		$re = '/(?<![\w>$:\\\\])' . preg_quote($fn, '/') . '\s*\(/';
		$hits = [];
		foreach ($this->sourcePhpFiles() as $file) {
			foreach (explode("\n", (string) file_get_contents($file)) as $n => $line) {
				$trimmed = ltrim($line);
				if ($trimmed === '' || $trimmed[0] === '*'
					|| str_starts_with($trimmed, '//')
					|| str_starts_with($trimmed, '/*')
					|| str_starts_with($trimmed, '#')) {
					continue;
				}
				if (preg_match($re, $line)) {
					$hits[] = basename($file) . ':' . ($n + 1);
				}
			}
		}
		return $hits;
	}

	/**
	 * Residue 1: add_translation() (removed in 5.0) must not be called anywhere
	 * in shipped source. The known offender is Config::registerLabels().
	 */
	public function testNoLiveAddTranslationCall(): void {
		$hits = $this->liveCallSites('add_translation');
		$this->assertSame(
			[],
			$hits,
			"add_translation() was removed in Elgg 5.0 and fatals on 7.x — "
			. "labels belong in languages/en.php, drop the runtime call:\n"
			. implode("\n", $hits)
		);
	}

	/**
	 * Residue 1 (targeted): Config.php must not contain a live add_translation(
	 * call, and the dynamic-label capability it provided must still exist.
	 *
	 * This test originally also asserted that registerLabels() still existed
	 * ("sanity: registerLabels() is the method that owned the removed call").
	 * That assertion was wrong: it pinned an implementation detail rather than
	 * the invariant, and it forbade the correct fix. registerLabels() had no
	 * callers anywhere in the plugin (verified by grep across classes/, views/,
	 * actions/ and elgg-plugin.php), was marked @deprecated 6.0, and its entire
	 * body was already duplicated by languages/en.php:143-153, which computes
	 * the same dynamic per-message-type keys and returns them in the
	 * $translations array — the Elgg 5.x+ replacement for add_translation().
	 * Keeping an uncallable, dead, fatal-on-entry method to satisfy a sanity
	 * check is strictly worse than deleting it, so the method is gone.
	 *
	 * The existence pin is replaced below by an assertion on the invariant that
	 * actually matters: the dynamic labels are still registered, just from the
	 * language file instead of a removed runtime call. That is a stronger
	 * guarantee than the original — it would catch someone deleting the
	 * add_translation() call *and* the language-file replacement together.
	 */
	public function testConfigSourceHasNoAddTranslation(): void {
		$src = (string) file_get_contents($this->pluginRoot() . '/classes/hypeJunction/Inbox/Config.php');

		$this->assertDoesNotMatchRegularExpression(
			'/(?<![\w>$:\\\\])add_translation\s*\(/',
			$src,
			'Config.php still calls removed add_translation() — registerLabels() fatals on 7.x'
		);

		$lang = (string) file_get_contents($this->pluginRoot() . '/languages/en.php');
		$this->assertMatchesRegularExpression(
			'/\$translations\[\$ruleset->getSingularLabel\(false\)\]/',
			$lang,
			'languages/en.php must still register the dynamic per-message-type labels '
			. 'that add_translation() used to register at runtime'
		);
	}

	/**
	 * Residue 2: a root deactivate.php is a forbidden bootstrap file on Elgg
	 * 4.x+ (the plugin is rejected at activation). It must not exist.
	 */
	public function testNoRootDeactivatePhp(): void {
		$this->assertFileDoesNotExist(
			$this->pluginRoot() . '/deactivate.php',
			'root deactivate.php is rejected on Elgg 4.x+ activation — move its logic '
			. 'into an Upgrade\\Batch or delete it'
		);
	}

	/**
	 * Residue 2: update_subtype() was removed from core and is not in the
	 * generic guard's removed-functions map. No shipped source may call it —
	 * the known offender is the leftover deactivate.php.
	 */
	public function testNoLiveUpdateSubtypeCall(): void {
		$hits = $this->liveCallSites('update_subtype');
		$this->assertSame(
			[],
			$hits,
			"update_subtype() was removed from core and fatals when reached:\n"
			. implode("\n", $hits)
		);
	}
}
