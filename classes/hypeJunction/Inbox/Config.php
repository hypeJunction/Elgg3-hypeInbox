<?php

namespace hypeJunction\Inbox;

use Elgg\Event;

/**
 * Config class.
 */
class Config {

	/** @var mixed */
    private $messageTypes;

	/** @var mixed */
    private $userTypes;

	/** @var mixed */
    private $userRelationships;

	/** @var mixed */
    private $userGroupRelationships;

	const TYPE_NOTIFICATION = '__notification';
	const TYPE_PRIVATE = '__private';

	/** @var mixed */
    private $plugin;

	/** @var mixed */
    private $settings;

	/**
	 * Constructor
	 * @param \ElggPlugin $plugin ElggPlugin
	 */
	public function __construct(\ElggPlugin $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * Returns config value
	 *
	 * @param string $name Config parameter name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Returns all plugin settings
	 * @return array
	 */
	public function all() {
		if (!isset($this->settings)) {
			$this->settings = array_merge($this->getDefaults(), $this->plugin->getAllSettings());
		}

		return $this->settings;
	}

	/**
	 * Returns a plugin setting
	 *
	 * @param string $name    Setting name
	 * @param mixed  $default Default value
	 * @return mixed
	 */
	public function get($name, $default = null) {
		return elgg_extract($name, $this->all(), $default);
	}

	/**
	 * Returns plugin path
	 * @return string
	 */
	public function getPath() {
		return $this->plugin->getPath();
	}

	/**
	 * Returns plugin ID
	 * @return string
	 */
	public function getID() {
		return $this->plugin->getID();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDefaults() {
		return [
			'dbprefix' => elgg_get_config('dbprefix'),
			'pagehandler_id' => 'messages',
		];
	}

	/**
	 * Initializes config values on system init
	 * @return void
	 */
	public function setLegacyConfig() {

		// legacy definitions
		define('HYPEINBOX', 'hypeinbox');
		define('HYPEINBOX_NOTIFICATION', self::TYPE_NOTIFICATION);
		define('HYPEINBOX_PRIVATE', self::TYPE_PRIVATE);
	}

	/**
	 * Registers label translations
	 * @return void
	 * @deprecated 6.0
	 */
	public function registerLabels() {
		$message_types = $this->getMessageTypes();

		// Register label translations for custom message types
		foreach ($message_types as $type => $options) {
			$ruleset = $this->getRuleset($type);
			add_translation('en', [
				$ruleset->getSingularLabel(false) => $ruleset->getSingularLabel('en'),
				$ruleset->getPluralLabel(false) => $ruleset->getPluralLabel('en')
			]);
		}
	}

	/**
	 * Returns an array of predefined and admin defined message types
	 * @return array
	 */
	public function getMessageTypes() {
		if (!isset($this->messageTypes)) {
			$default_message_types = $this->getSetting('default_message_types');
			$message_types = $this->getSetting('message_types');
			$this->messageTypes = array_merge($default_message_types, $message_types);
		}

		return $this->messageTypes;
	}

	/**
	 * Returns a set of rules for a given message type
	 *
	 * @param string $message_type Message type
	 * @return Ruleset
	 */
	public function getRuleset($message_type = '') {
		$ruleset = [];
		$types = $this->getMessageTypes();
		if (isset($types[$message_type])) {
			$ruleset = $types[$message_type];
		}

		return new Ruleset($message_type, $ruleset);
	}

	/**
	 * Filters an array of configured sender and recipient types
	 * These will be used when applying message type rules
	 * - 'validation' callback function will be used to identify whether or not a user belongs to that user type group
	 *    (user entity will be passed to this callback function)
	 * - 'getter' callback function will be used to populate tokeninput options
	 *
	 * Use 'config:user_types','framework:inbox' plugin hook to extend this array
	 * Callbacks should only return an array with 'joins' and 'wheres'. User table will be joined automatically with 'ue' prefix
	 * @return array
	 */
	public function getUserTypes() {
		if (!isset($this->userTypes)) {
			$config = [
				'all' => [],
				'admin' => [
					'validator' => [hypeInbox()->model, 'isAdminUser'],
					'getter' => [hypeInbox()->model, 'getAdminQueryOptions'],
				],
			];

			$this->userTypes = elgg_trigger_event_results('config:user_types', 'framework:inbox', [], $config);
		}

		return $this->userTypes;
	}

	/**
	 * Get a list of existing <user>-<user> relationships
	 * @return array
	 */
	public function getUserRelationships() {

		if (!isset($this->userRelationships)) {
			$relationships = [];

			$query = "SELECT DISTINCT(er.relationship)
				FROM {$this->dbprefix}entity_relationships er
				JOIN {$this->dbprefix}entities e1 ON e1.guid = er.guid_one
				JOIN {$this->dbprefix}entities e2 ON e2.guid = er.guid_two
				WHERE (e1.type = 'user' AND e2.type = 'user')";

			$data = get_data($query);
			foreach ($data as $rel) {
				$relationships[] = $rel->relationship;
			}

			$this->userRelationships = $relationships;
		}

		return $this->userRelationships;
	}

	/**
	 * Get a list of existing <user>-<group> relationships
	 * @return array
	 */
	public function getUserGroupRelationships() {

		if (!isset($this->userGroupRelationships)) {
			$relationships = [];

			$query = "SELECT DISTINCT(er.relationship)
				FROM {$this->dbprefix}entity_relationships er
				JOIN {$this->dbprefix}entities e1 ON e1.guid = er.guid_one
				JOIN {$this->dbprefix}entities e2 ON e2.guid = er.guid_two
				WHERE (e1.type = 'user' AND e2.type = 'group')";

			$data = get_data($query);
			foreach ($data as $rel) {
				$relationships[] = $rel->relationship;
			}

			$this->userGroupRelationships = $relationships;
		}

		return $this->userGroupRelationships;
	}

	/**
	 * Returns an unserialize plugin setting value
	 *
	 * @param string $name Plugin setting name
	 * @return array
	 */
	public function getSetting($name = '') {
		$value = $this->$name;
		if (!is_string($value)) {
			return [];
		}

		$decoded = json_decode($value, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			return (array) $decoded;
		}

		// Legacy: stored as serialize() before 5.x migration — safe because
		// allowed_classes=false prevents PHP object injection.
		return (array) unserialize($value, ['allowed_classes' => false]);
	}

	/**
	 * Add third party user types/roles to the config array
	 *
	 * @param Event $event Event
	 * @return array
	 */
	public static function filterUserTypes(Event $event) {
		$return = $event->getValue();

		if (elgg_is_active_plugin('roles')) {
			$roles = roles_get_all_selectable_roles();
			foreach ($roles as $role) {
				$return[$role->name] = [
					'validator' => [hypeInbox()->model, 'hasRole'],
					'getter' => [hypeInbox()->model, 'getRoleTestQuery'],
				];
			}
		}

		return $return;
	}
}
