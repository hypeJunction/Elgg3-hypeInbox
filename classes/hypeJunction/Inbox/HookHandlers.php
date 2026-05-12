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
	 * @param string $hook   "config:user_types"
	 * @param string $type   "framework:inbox"
	 * @param array  $return User types config array
	 * @param array  $params Hook params
	 * @return array
	 * @deprecated 6.0
	 */
	public function filterUserTypes(\Elgg\Event $event) {
		return Config::filterUserTypes($event);
	}

	/**
	 * Messages page menu setup
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:page"
	 * @param array  $return An array of menu items
	 * @param array  $params Additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupPageMenu(\Elgg\Event $event) {
		return Menus::setupPageMenu($event);
	}

	/**
	 * Register user hover menu items
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:user_hover"
	 * @param array  $return An array of menu items
	 * @param array  $params Additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupUserHoverMenu(\Elgg\Event $event) {
		return Menus::setupUserHoverMenu($event);
	}

	/**
	 * Message entity menu setup
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:entity"
	 * @param array  $return An array of menu items
	 * @param array  $params An array of additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupMessageMenu(\Elgg\Event $event) {
		return Menus::setupMessageMenu($event);
	}

	/**
	 * Inbox controls setup
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:inbox"
	 * @param array  $return An array of menu items
	 * @param array  $params An array of additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupInboxMenu(\Elgg\Event $event) {
		return Menus::setupInboxMenu($event);
	}

	/**
	 * Thread controls setup
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:inbox:thread"
	 * @param array  $return An array of menu items
	 * @param array  $params An array of additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupInboxThreadMenu(\Elgg\Event $event) {
		return Menus::setupInboxThreadMenu($event);
	}

	/**
	 * Setup topbar menu
	 *
	 * @param string         $hook   "register"
	 * @param string         $type   "menu:topbar"
	 * @param ElggMenuItem[] $return Menu
	 * @param array          $params Hook params
	 * @return ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupTopbarMenu(\Elgg\Event $event) {
		return Menus::setupTopbarMenu($event);
	}

	/**
	 * Pretty URL for message objects
	 *
	 * @param string $hook   "entity:url"
	 * @param string $type   "object"
	 * @param string $return Icon URL
	 * @param array  $params Hook params
	 * @return string Filtered URL
	 */
	public function handleMessageURL(\Elgg\Event $event) {
		return Router::messageUrlHandler($event);
	}

	/**
	 * Replace message icon with a sender icon
	 *
	 * @param string $hook   "entity:icon:url"
	 * @param string $type   "object"
	 * @param string $return Icon URL
	 * @param array  $params Hook params
	 * @return string Filtered URL
	 * @deprecated 6.0
	 */
	public function handleMessageIconURL(\Elgg\Event $event) {
		return Router::messageIconUrlHandler($event);
	}

	/**
	 * Get graph alias.
	 *
	 * @param string $hook   Hook name
	 * @param string $type   Hook type
	 * @param mixed  $return Return value
	 * @param array  $params Hook params
	 * @return mixed
	 * @deprecated 6.0
	 */
	public function getGraphAlias(\Elgg\Event $event) {
		return Graph::getGraphAlias($event);
	}

	/**
	 * Get message properties.
	 *
	 * @param string $hook   Hook name
	 * @param string $type   Hook type
	 * @param mixed  $return Return value
	 * @param array  $params Hook params
	 * @return mixed
	 * @deprecated 6.0
	 */
	public function getMessageProperties(\Elgg\Event $event) {
		return Graph::getMessageProperties($event);
	}

	/**
	 * Add unread notifications count to the ajax responses
	 *
	 * @param string $hook   "output"
	 * @param string $type   "ajax"
	 * @param array  $return Ajax output
	 * @param array  $params Hook params
	 * @return array
	 * @deprecated 6.0
	 */
	public function ajaxOutput(\Elgg\Event $event) {
		return Ajax::setUnreadMessagesCount();
	}

	/**
	 * Register custom template
	 *
	 * @param string $hook   "get_templates"
	 * @param string $type   "notifications"
	 * @param string $return Template names
	 * @param array  $params Hook params
	 * @return array
	 * @deprecated 6.0
	 */
	public function addCustomTemplate(\Elgg\Event $event) {
		return \hypeJunction\Inbox\Notifiations::registerCustomTemplates($event);
	}
}
