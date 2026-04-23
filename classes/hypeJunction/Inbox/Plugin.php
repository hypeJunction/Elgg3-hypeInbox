<?php

namespace hypeJunction\Inbox;

use Elgg\Di\DiContainer;
use ElggPlugin;
use hypeJunction\Inbox\Models\Model;

/**
 * Inbox service provider
 *
 * @property-read ElggPlugin   $plugin
 * @property-read Config       $config
 * @property-read HookHandlers $hooks
 * @property-read Router	   $router
 * @property-read Model        $model
 */
final class Plugin extends DiContainer {

	/**
	 * Constructor
	 *
	 * @param ElggPlugin $plugin Plugin object
	 */
	public function __construct(ElggPlugin $plugin) {

		// PHP-DI v6 (Elgg 4.x): initialize the parent Container so that
		// $this->definitionSource is not null before calling set().
		parent::__construct();

		// use set() instead of the removed setValue/setFactory/setClassName
		// helpers from Elgg 3.x. Passing a Closure to set() wraps it in a
		// FactoryDefinition (lazy).
		$this->set('plugin', $plugin);

		$this->set('config', function () use ($plugin) {
			return new Config($plugin);
		});

		$this->set('hooks', function () {
			return new HookHandlers();
		});

		$this->set('router', function () {
			return new Router();
		});

		$self = $this;
		$this->set('model', function () use ($self) {
			return new Model($self->get('config'));
		});
	}

	/**
	 * @deprecated 6.0
	 */
	public static function factory(array $options = []) {
		return hypeInbox();
	}

	/**
	 * @deprecated 6.0
	 */
	public function boot() {}

	/**
	 * @deprecated 6.0
	 */
	public function init() {}

	/**
	 * {@inheritdoc}
	 */
	public static function getDefinitionSources(): array {
		return [];
	}
}