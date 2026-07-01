<?php

namespace hypeJunction\Inbox;

use ElggMenuItem;
use hypeJunction\Inbox\Config;

/**
 * Plugin hooks service
 */
class HookHandlers {

	/**
	 * Add third party user types/roles to the config array
	 *
	 * @param \Elgg\Event $event Event
	 * @return array
	 * @deprecated 6.0
	 */
	public function filterUserTypes(\Elgg\Event $event) {
		return Config::filterUserTypes($event);
	}

	/**
	 * Messages page menu setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupPageMenu(\Elgg\Event $event) {
		return Menus::setupPageMenu($event);
	}

	/**
	 * Register user hover menu items
	 *
	 * @param \Elgg\Event $event Event
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupUserHoverMenu(\Elgg\Event $event) {
		return Menus::setupUserHoverMenu($event);
	}

	/**
	 * Message entity menu setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupMessageMenu(\Elgg\Event $event) {
		return Menus::setupMessageMenu($event);
	}

	/**
	 * Inbox controls setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupInboxMenu(\Elgg\Event $event) {
		return Menus::setupInboxMenu($event);
	}

	/**
	 * Thread controls setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupInboxThreadMenu(\Elgg\Event $event) {
		return Menus::setupInboxThreadMenu($event);
	}

	/**
	 * Setup topbar menu
	 *
	 * @param \Elgg\Event $event Event
	 * @return ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupTopbarMenu(\Elgg\Event $event) {
		return Menus::setupTopbarMenu($event);
	}

	/**
	 * Pretty URL for message objects
	 *
	 * @param \Elgg\Event $event Event
	 * @return string Filtered URL
	 */
	public function handleMessageURL(\Elgg\Event $event) {
		return Router::messageUrlHandler($event);
	}

	/**
	 * Replace message icon with a sender icon
	 *
	 * @param \Elgg\Event $event Event
	 * @return string Filtered URL
	 * @deprecated 6.0
	 */
	public function handleMessageIconURL(\Elgg\Event $event) {
		return Router::messageIconUrlHandler($event);
	}

	/**
	 * Get graph alias.
	 *
	 * @param \Elgg\Event $event Event
	 * @return mixed
	 * @deprecated 6.0
	 */
	public function getGraphAlias(\Elgg\Event $event) {
		return Graph::getGraphAlias($event);
	}

	/**
	 * Get message properties.
	 *
	 * @param \Elgg\Event $event Event
	 * @return mixed
	 * @deprecated 6.0
	 */
	public function getMessageProperties(\Elgg\Event $event) {
		return Graph::getMessageProperties($event);
	}

	/**
	 * Add unread notifications count to the ajax responses
	 *
	 * @param \Elgg\Event $event Event
	 * @return array
	 * @deprecated 6.0
	 */
	public function ajaxOutput(\Elgg\Event $event) {
		return Ajax::setUnreadMessagesCount();
	}

	/**
	 * Register custom template
	 *
	 * @param \Elgg\Event $event Event
	 * @return array
	 * @deprecated 6.0
	 */
	public function addCustomTemplate(\Elgg\Event $event) {
		return \hypeJunction\Inbox\Notifiations::registerCustomTemplates($event);
	}
}
