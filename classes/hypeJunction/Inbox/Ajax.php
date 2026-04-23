<?php

namespace hypeJunction\Inbox;

use Elgg\Event;

class Ajax {

	public static function setUnreadMessagesCount(Event $event) {
		$return = $event->getValue();
		$return['inbox']['unread'] = (int) hypeInbox()->model->countUnreadMessages();
		return $return;
	}
}
