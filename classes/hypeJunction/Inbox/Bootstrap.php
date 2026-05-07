<?php

namespace hypeJunction\Inbox;

use Elgg\PluginBootstrap;

/**
 * Bootstrap class.
 */
class Bootstrap extends PluginBootstrap {

	/**
	 * getPath.
	 *
	 * @return mixed
	 */
	public function getPath() {
		return $this->plugin->getPath();
	}

	/**
	 * load.
	 *
	 * @return mixed
	 */
	public function load() {
		require_once $this->getPath() . 'autoloader.php';
	}

	/**
	 * boot.
	 *
	 * @return mixed
	 */
	public function boot() {
	}

	/**
	 * init.
	 *
	 * @return mixed
	 */
	public function init() {
		elgg_extend_view('elgg.css', 'framework/inbox.css');
		elgg_extend_view('elgg.js', 'framework/inbox/message.js');

		// URL and page handling
		elgg_register_event_handler('page_owner', 'system', [Router::class, 'resolvePageOwner']);
		elgg_register_event_handler('entity:url', 'object', [Router::class, 'messageUrlHandler']);
		elgg_register_event_handler('entity:icon:url', 'object', [Router::class, 'messageIconUrlHandler']);

		// Third party integrations
		elgg_register_event_handler('config:user_types', 'framework:inbox', [Config::class, 'filterUserTypes']);

		elgg_unregister_event_handler('register', 'menu:user_hover', 'messages_user_hover_menu');

		elgg_register_event_handler('register', 'menu:page', [Menus::class, 'setupPageMenu']);
		elgg_register_event_handler('register', 'menu:page', [Menus::class, 'setupAdminPageMenu']);
		elgg_register_event_handler('register', 'menu:page', [Menus::class, 'setupInboxThreadMenu']);
		elgg_register_event_handler('register', 'menu:inbox', [Menus::class, 'setupInboxMenu']);
		elgg_register_event_handler('register', 'menu:entity', [Menus::class, 'setupMessageMenu']);
		elgg_register_event_handler('register', 'menu:user_hover', [Menus::class, 'setupUserHoverMenu']);
		elgg_register_event_handler('register', 'menu:title', [Menus::class, 'setupTitleMenu']);

		// (4.x) Graph API export removed — bodyology uses a Nuxt frontend
		// with its own API layer, and the hypeApps Property/Values shim is
		// no longer maintained for Elgg 4.x. See bodyology/MIGRATION.md
		// "Replacement plan #3" (Option A) and bead elgg-migrate-fflc.

		// Top bar
		elgg_unregister_event_handler('register', 'menu:topbar', 'messages_register_topbar');
		elgg_register_event_handler('register', 'menu:topbar', [Menus::class, 'setupTopbarMenu'], 800);
		elgg_register_event_handler('output', 'ajax', [Ajax::class, 'setUnreadMessagesCount']);
		elgg_extend_view('page/elements/topbar', 'framework/inbox/popup');

		// Notification Templates
		elgg_register_event_handler('get_templates', 'notifications', [
			Notifications::class,
			'registerCustomTemplates'
		]);
	}

	/**
	 * ready.
	 *
	 * @return mixed
	 */
	public function ready() {
	}

	/**
	 * shutdown.
	 *
	 * @return mixed
	 */
	public function shutdown() {
	}

	/**
	 * activate.
	 *
	 * @return mixed
	 */
	public function activate() {
		$message_types = [
			'__private' => [
				'labels' => [
					'singular' => 'Private Message',
					'plural' => 'Private Messages',
				],
				'multiple' => true,
				'attachments' => true,
				'persistent' => false,
				'allowed_senders' => [
					'all'
				],
				'policy' => [
					[
						'sender' => 'all',
						'recipient' => 'all',
					]
				],
			],
		];

		if (is_null($this->plugin->getSetting('default_message_types'))) {
			$this->plugin->setSetting('default_message_types', json_encode($message_types));
		}
	}

	/**
	 * deactivate.
	 *
	 * @return mixed
	 */
	public function deactivate() {
	}

	/**
	 * upgrade.
	 *
	 * @return mixed
	 */
	public function upgrade() {
	}
}
