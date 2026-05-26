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
	public function filterUserTypes(\Elgg\Hook $hook) {
		return Config::filterUserTypes($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
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
	public function setupPageMenu(\Elgg\Hook $hook) {
		return Menus::setupPageMenu($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
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
	public function setupUserHoverMenu(\Elgg\Hook $hook) {
		return Menus::setupUserHoverMenu($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

	/**
	 * Message entity menu setup
	 *
	 * @param string $hook "register"
	 * @param string $type "menu:entity"
	 * @param array $return An array of menu items
	 * @param array $params An array of additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupMessageMenu(\Elgg\Hook $hook) {
		return Menus::setupMessageMenu($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

	/**
	 * Inbox controls setup
	 *
	 * @param string $hook "register"
	 * @param string $type "menu:inbox"
	 * @param array $return An array of menu items
	 * @param array $params An array of additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupInboxMenu(\Elgg\Hook $hook) {
		return Menus::setupInboxMenu($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

	/**
	 * Thread controls setup
	 *
	 * @param string $hook "register"
	 * @param string $type "menu:inbox:thread"
	 * @param array $return An array of menu items
	 * @param array $params An array of additional parameters
	 * @return array An array of menu items
	 * @deprecated 6.0
	 */
	public function setupInboxThreadMenu(\Elgg\Hook $hook) {
		return Menus::setupInboxThreadMenu($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

	/**
	 * Setup topbar menu
	 *
	 * @param string         $hook   "register"
	 * @param string         $type   "menu:topbar"
	 * @param ElggMenuItem[] $return  Menu
	 * @param array          $params  Hook params
	 * @return ElggMenuItem[]
	 * @deprecated 6.0
	 */
	public function setupTopbarMenu(\Elgg\Hook $hook) {
		return Menus::setupTopbarMenu($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
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
	public function handleMessageURL(\Elgg\Hook $hook) {
		return Router::messageUrlHandler($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
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
	public function handleMessageIconURL(\Elgg\Hook $hook) {
		return Router::messageIconUrlHandler($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

	/**
	 * @deprecated 6.0
	 */
	public function getGraphAlias(\Elgg\Hook $hook) {
		return Graph::getGraphAlias($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

	/**
	 * @deprecated 6.0
	 */
	public function getMessageProperties(\Elgg\Hook $hook) {
		return Graph::getMessageProperties($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
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
	public function ajaxOutput(\Elgg\Hook $hook) {
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
	public function addCustomTemplate(\Elgg\Hook $hook) {
		return \hypeJunction\Inbox\Notifiations::registerCustomTemplates($hook, $hook->getType(), $hook->getValue(), $hook->getParams());
	}

}
