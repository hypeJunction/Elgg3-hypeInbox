<?php

namespace hypeJunction\Inbox;

use Elgg\Event;

/**
 * Ajax class.
 */
class Ajax {

	/**
	 * setUnreadMessagesCount.
	 *
	 * @param Event $event event
	 *
	 * @return mixed
	 */
	public static function setUnreadMessagesCount(Event $event) {
		$return = $event->getValue();
		$return['inbox']['unread'] = (int) hypeInbox()->model->countUnreadMessages();
		return $return;
	}
}
