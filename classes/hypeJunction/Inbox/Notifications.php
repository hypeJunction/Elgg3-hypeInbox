<?php

namespace hypeJunction\Inbox;

use Elgg\Event;

class Notifications {

	public static function registerCustomTemplates(Event $event) {
		$return = $event->getValue();
		$return[] = "messages_send";
		return $return;
	}
}
