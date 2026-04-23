<?php

namespace hypeJunction\Inbox;

use ElggEntity;
use Elgg\Event;
use hypeJunction\Inbox\Message;

class Router {

	public static function resolvePageOwner(Event $event) {

		if ($event->getValue()) {
			return;
		}

		$segments = _elgg_services()->request->getUrlSegments();
		$identifier = array_shift($segments);

		if ($identifier !== 'messages') {
			return;
		}

		$page = array_shift($segments) ? : 'inbox';

		switch ($page) {

			case 'read' :
			case 'view' :
			case 'reply' :
			case 'compose' :
			case 'add' :
				$guid = array_shift($segments);
				if (!$guid) {
					return;
				}
				$entity = get_entity($guid);
				if (!$entity) {
					return;
				}
				$container = $entity->getContainerEntity();
				if (!$container) {
					return;
				}
				return $container->guid;

			case 'inbox' :
			case 'incoming' :
			case 'outbox' :
			case 'outgoing' :
			case 'sent' :
			case 'received' :
			case 'search' :
				$username = array_shift($segments);
				if ($username) {
					$user = elgg_get_user_by_username($username);
				} else {
					$user = elgg_get_logged_in_user_entity();
				}
				if (!$user) {
					return;
				}
				return $user->guid;
		}
	}

	public static function messageUrlHandler(Event $event) {

		$entity = $event->getParam('entity');

		if (!$entity instanceof Message) {
			return $event->getValue();
		}

		return elgg_normalize_url("messages/read/$entity->guid#elgg-object-$entity->guid");
	}

	public static function messageIconUrlHandler(Event $event) {

		$entity = $event->getParam('entity');
		$size = $event->getParam('size');

		if (!$entity instanceof Message) {
			return $event->getValue();
		}

		$sender = $entity->getSender();
		if ($sender) {
			return $sender->getIconURL($size);
		}
	}

	public function getPageHandlerId() {
		return hypeInbox()->config->get('pagehandler_id', 'messages');
	}

	public function getMessageURL(Message $entity) {
		$friendly = elgg_get_friendly_title($entity->getDisplayName());
		return $this->normalize(array('read', $entity->guid, $friendly . "#elgg-object-{$entity->guid}"));
	}

	public function normalize($url = '', $query = array()) {

		if (is_array($url)) {
			$url = implode('/', $url);
		}

		$url = implode('/', array($this->getPageHandlerId(), $url));

		if (!empty($query)) {
			$url = elgg_http_add_url_query_elements($url, $query);
		}

		return elgg_normalize_url($url);
	}

	public function getPageOwner($segments = array()) {

		$owner = elgg_get_logged_in_user_entity();

		if (is_array($segments)) {
			foreach ($segments as $segment) {
				$user = elgg_get_user_by_username($segment);
				if ($user instanceof \ElggUser) {
					$owner = $user;
					break;
				}
			}
		}

		return $owner;
	}

}
