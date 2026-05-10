<?php

namespace hypeJunction\Inbox;

use Elgg\Event;

/**
 * Notifications class.
 */
class Notifications {

	/**
	 * registerCustomTemplates.
	 *
	 * @param Event $event event
	 *
	 * @return mixed
	 */
	public static function registerCustomTemplates(Event $event) {
		$return = $event->getValue();
		$return[] = 'messages_send';
		return $return;
	}
}
