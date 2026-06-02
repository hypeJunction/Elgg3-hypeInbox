<?php

namespace hypeJunction\Inbox;

use Elgg\Event;

/**
 * Plugin hooks service (legacy BC shim).
 *
 * Elgg 5.x merged hooks into events: handlers receive a single
 * \Elgg\Event. These methods are deprecated delegation wrappers kept for
 * backward compatibility; the live handlers are registered directly on the
 * delegate classes (Menus, Router, Config, Ajax, Notifications) in Bootstrap.
 */
class HookHandlers {

	/**
	 * Add third party user types/roles to the config array
	 *
	 * @param \Elgg\Event $event Event
	 * @return array
	 * @deprecated 6.0
	 */
	public function filterUserTypes(Event $event) {
		return Config::filterUserTypes($event);
	}

	/**
	 * Messages page menu setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return \ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupPageMenu(Event $event) {
		return Menus::setupPageMenu($event);
	}

	/**
	 * Register user hover menu items
	 *
	 * @param \Elgg\Event $event Event
	 * @return \ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupUserHoverMenu(Event $event) {
		return Menus::setupUserHoverMenu($event);
	}

	/**
	 * Message entity menu setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return \ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupMessageMenu(Event $event) {
		return Menus::setupMessageMenu($event);
	}

	/**
	 * Inbox controls setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return \ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupInboxMenu(Event $event) {
		return Menus::setupInboxMenu($event);
	}

	/**
	 * Thread controls setup
	 *
	 * @param \Elgg\Event $event Event
	 * @return \ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupInboxThreadMenu(Event $event) {
		return Menus::setupInboxThreadMenu($event);
	}

	/**
	 * Setup topbar menu
	 *
	 * @param \Elgg\Event $event Event
	 * @return \ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupTopbarMenu(Event $event) {
		return Menus::setupTopbarMenu($event);
	}

	/**
	 * Pretty URL for message objects
	 *
	 * @param \Elgg\Event $event Event
	 * @return string Filtered URL
	 * @deprecated 6.0
	 */
	public function handleMessageURL(Event $event) {
		return Router::messageUrlHandler($event);
	}

	/**
	 * Replace message icon with a sender icon
	 *
	 * @param \Elgg\Event $event Event
	 * @return string Filtered URL
	 * @deprecated 6.0
	 */
	public function handleMessageIconURL(Event $event) {
		return Router::messageIconUrlHandler($event);
	}

	/**
	 * Add unread notifications count to the ajax responses
	 *
	 * @param \Elgg\Event $event Event
	 * @return array
	 * @deprecated 6.0
	 */
	public function ajaxOutput(Event $event) {
		return Ajax::setUnreadMessagesCount($event);
	}

	/**
	 * Register custom template
	 *
	 * @param \Elgg\Event $event Event
	 * @return array
	 * @deprecated 6.0
	 */
	public function addCustomTemplate(Event $event) {
		return Notifications::registerCustomTemplates($event);
	}
}
