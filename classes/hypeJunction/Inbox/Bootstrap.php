<?php

namespace hypeJunction\Inbox;

use Elgg\PluginBootstrap;

class Bootstrap extends PluginBootstrap {

	public function getPath() {
		return $this->plugin->getPath();
	}

	public function load() {
		require_once $this->getPath() . 'autoloader.php';
	}

	public function boot() {

	}

	public function init() {
		elgg_extend_view('elgg.css', 'framework/inbox.css');
		elgg_extend_view('elgg.js', 'framework/inbox/message.js');

		// URL and page handling
		elgg_register_plugin_hook_handler('page_owner', 'system', [Router::class, 'resolvePageOwner']);
		elgg_register_plugin_hook_handler('entity:url', 'object', [Router::class, 'messageUrlHandler']);
		elgg_register_plugin_hook_handler('entity:icon:url', 'object', [Router::class, 'messageIconUrlHandler']);

		// Third party integrations
		elgg_register_plugin_hook_handler('config:user_types', 'framework:inbox', [Config::class, 'filterUserTypes']);

		elgg_unregister_plugin_hook_handler('register', 'menu:user_hover', 'messages_user_hover_menu');

		elgg_register_plugin_hook_handler('register', 'menu:page', [Menus::class, 'setupPageMenu']);
		elgg_register_plugin_hook_handler('register', 'menu:page', [Menus::class, 'setupAdminPageMenu']);
		elgg_register_plugin_hook_handler('register', 'menu:page', [Menus::class, 'setupInboxThreadMenu']);
		elgg_register_plugin_hook_handler('register', 'menu:inbox', [Menus::class, 'setupInboxMenu']);
		elgg_register_plugin_hook_handler('register', 'menu:entity', [Menus::class, 'setupMessageMenu']);
		elgg_register_plugin_hook_handler('register', 'menu:user_hover', [Menus::class, 'setupUserHoverMenu']);
		elgg_register_plugin_hook_handler('register', 'menu:title', [Menus::class, 'setupTitleMenu']);

		// Export
		if (elgg_is_active_plugin('hypeApps')) {
			elgg_register_plugin_hook_handler('aliases', 'graph', [Graph::class, 'getGraphAlias']);
			elgg_register_plugin_hook_handler('graph:properties', 'object:messages', [
				Graph::class,
				'getMessageProperties'
			]);
		}

		// Top bar
		elgg_unregister_plugin_hook_handler('register', 'menu:topbar', 'messages_register_topbar');
		elgg_register_plugin_hook_handler('register', 'menu:topbar', [Menus::class, 'setupTopbarMenu'], 800);
		elgg_register_plugin_hook_handler('output', 'ajax', [Ajax::class, 'setUnreadMessagesCount']);
		elgg_extend_view('page/elements/topbar', 'framework/inbox/popup');

		// Notification Templates
		elgg_register_plugin_hook_handler('get_templates', 'notifications', [
			Notifications::class,
			'registerCustomTemplates'
		]);
	}

	public function ready() {

	}

	public function shutdown() {

	}

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

		if (is_null(elgg_get_plugin_setting('default_message_types', 'hypeInbox'))) {
			elgg_set_plugin_setting('default_message_types', serialize($message_types), 'hypeInbox');
		}
	}

	public function deactivate() {

	}

	public function upgrade() {

	}
}