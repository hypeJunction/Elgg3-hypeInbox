<?php

namespace hypeJunction\Inbox\Upgrades;

use Elgg\Upgrade\Batch;
use Elgg\Upgrade\Result;

/**
 * Migrate hypeinbox plugin settings from serialize() to json_encode().
 *
 * Prior to 5.x, Bootstrap::activate() and actions/settings/save.php stored
 * array-valued plugin settings with serialize(). The 5.x migration switches
 * all writes to json_encode(). This batch rewrites any existing serialized
 * values so that Config::getSetting() only needs the JSON path going forward.
 */
class MigrateSettingsToJson extends Batch {

	/** @var string[] Setting keys that store serialized arrays */
	const ARRAY_SETTINGS = ['default_message_types', 'message_types'];

	/**
     * @return int
     */
    public function getVersion(): int {
		return 2026042301;
	}

	/**
     * @return bool
     */
    public function needsIncrementOffset(): bool {
		return false;
	}

	/**
     * @return bool
     */
    public function shouldBeSkipped(): bool {
		return false;
	}

	/**
     * @return int
     */
    public function countItems(): int {
		return count(self::ARRAY_SETTINGS);
	}

	/**
     * @param Result $result
     * @param mixed $offset
     * @return Result
     */
    public function run(Result $result, $offset): Result {
		$plugin = \elgg_get_plugin_from_id('hypeinbox');
		if (!$plugin) {
			$result->addFailures(count(self::ARRAY_SETTINGS));
			return $result;
		}

		foreach (self::ARRAY_SETTINGS as $key) {
			$value = $plugin->getSetting($key);
			if ($value === null) {
				$result->addSuccesses();
				continue;
			}

			if (!is_string($value)) {
				$result->addSuccesses();
				continue;
			}

			// Already JSON — nothing to migrate.
			json_decode($value, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$result->addSuccesses();
				continue;
			}

			// Serialized — migrate to JSON.
			$decoded = unserialize($value, ['allowed_classes' => false]);
			if ($decoded === false) {
				$result->addFailures();
				continue;
			}

			if ($plugin->setSetting($key, json_encode($decoded))) {
				$result->addSuccesses();
			} else {
				$result->addFailures();
			}
		}

		return $result;
	}
}
