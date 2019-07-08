<?php

namespace hypeJunction\Inbox;

use Elgg\HooksRegistrationService\Hook;
use ElggMenuItem;

class Menus {

	public static function setupPageMenu(Hook $hook) {
		if (!elgg_in_context('messages')) {
			return;
		}

		$entity = $hook->getEntityParam();
		if ($entity instanceof \ElggEntity) {
			return;
		}

		$user = elgg_get_page_owner_entity();

		$menu = $hook->getValue();

		$intypes = hypeInbox()->model->getIncomingMessageTypes($user);

		$menu->add(ElggMenuItem::factory([
			'name' => 'inbox',
			'text' => elgg_echo('inbox:inbox'),
			'href' => count($intypes) > 1 ? false : elgg_generate_url('collection:object:messages:owner', [
				'message_type' => $intypes[0],
			]),
			'priority' => 100,
			'link_class' => 'inbox-load',
			'icon' => 'fas fa-inbox',
		]));

		if (count($intypes) > 1) {
			foreach ($intypes as $type) {
				$menu->add(ElggMenuItem::factory([
					'name' => "inbox:$type",
					'parent_name' => 'inbox',
					'text' => elgg_echo("item:object:message:$type:plural"),
					'href' => elgg_generate_url('collection:object:messages:owner', [
						'message_type' => $type,
					]),
					'link_class' => 'inbox-load'
				]));
			}
		}

		$outtypes = hypeInbox()->model->getOutgoingMessageTypes($user);

		$menu->add(ElggMenuItem::factory([
			'name' => 'sentmessages',
			'text' => elgg_echo('inbox:sent'),
			'href' => count($outtypes) > 1 ? false : elgg_generate_url('collection:object:messages:sent', [
				'message_type' => $outtypes[0],
			]),
			'priority' => 500,
			'link_class' => 'inbox-load',
			'icon' => 'fas fa-history',
		]));

		if (count($outtypes) > 1) {
			foreach ($outtypes as $type) {
				$menu->add(ElggMenuItem::factory([
					'name' => "sent:$type",
					'parent_name' => 'sentmessages',
					'text' => elgg_echo("item:object:message:$type:plural"),
					'href' => elgg_generate_url('collection:object:messages:sent', [
						'message_type' => $type,
					]),
					'link_class' => 'inbox-load'
				]));
			}
		}

		$menu->add(ElggMenuItem::factory([
			'name' => 'inbox:search',
			'text' => elgg_echo('inbox:search'),
			'href' => elgg_generate_url('collection:object:messages:search'),
			'priority' => 800,
			'link_class' => 'inbox-load',
			'icon' => 'fas fa-search',
		]));

		return $menu;
	}

	/**
	 * Admin menu setup
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:page"
	 * @param array  $return An array of menu items
	 * @param array  $params Additional parameters
	 *
	 * @return array An array of menu items
	 */
	public static function setupAdminPageMenu($hook, $type, $return, $params) {

		if (!elgg_in_context('admin')) {
			return;
		}

		$return[] = ElggMenuItem::factory([
			'name' => 'message_types',
			'text' => elgg_echo('admin:inbox:message_types'),
			'href' => 'admin/inbox/message_types',
			'priority' => 500,
			'contexts' => ['admin'],
			'section' => 'administer'
		]);

		return $return;
	}

	/**
	 * Register user hover menu items
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:user_hover"
	 * @param array  $return An array of menu items
	 * @param array  $params Additional parameters
	 *
	 * @return array An array of menu items
	 */
	public static function setupUserHoverMenu($hook, $type, $return, $params) {

		$recipient = elgg_extract('entity', $params);
		$sender = elgg_get_logged_in_user_entity();

		if (!$sender || !$recipient) {
			return $return;
		}

		if ($sender->guid == $recipient->guid) {
			return $return;
		}

		$message_types = hypeInbox()->config->getMessageTypes();
		$user_types = hypeInbox()->config->getUserTypes();

		foreach ($message_types as $type => $options) {

			if ($type == Config::TYPE_NOTIFICATION) {
				continue;
			}

			$valid = false;

			$policies = $options['policy'];
			if (!$policies) {
				$valid = true;
			} else {

				foreach ($policies as $policy) {

					$valid = false;

					$recipient_type = $policy['recipient'];
					$sender_type = $policy['sender'];
					$relationship = $policy['relationship'];
					$inverse_relationship = $policy['inverse_relationship'];
					$group_relationship = $policy['group_relationship'];

					$recipient_validator = $user_types[$recipient_type]['validator'];
					if ($recipient_type == 'all' ||
						($recipient_validator && is_callable($recipient_validator) && call_user_func($recipient_validator, $recipient, $recipient_type))) {

						$sender_validator = $user_types[$sender_type]['validator'];
						if ($sender_type == 'all' ||
							($sender_validator && is_callable($sender_validator) && call_user_func($sender_validator, $sender, $sender_type))) {

							$valid = true;
							if ($relationship && $relationship != 'all') {
								if ($inverse_relationship) {
									$valid = check_entity_relationship($recipient->guid, $relationship, $sender->guid);
								} else {
									$valid = check_entity_relationship($sender->guid, $relationship, $recipient->guid);
								}
							}
							if ($valid && $group_relationship && $group_relationship != 'all') {
								$dbprefix = elgg_get_config('dbprefix');
								$valid = elgg_get_entities_from_relationship([
									'types' => 'group',
									'relationship' => 'member',
									'relationship_guid' => $recipient->guid,
									'count' => true,
									'wheres' => [
										"EXISTS (SELECT * FROM {$dbprefix}entity_relationships
										WHERE guid_one = $sender->guid AND relationship = '$group_relationship' AND guid_two = r.guid_two)"
									]
								]);
							}
						}
					}

					if ($valid) {
						break;
					}
				}
			}
			if ($valid) {
				$return[] = ElggMenuItem::factory([
					'name' => "inbox:$type",
					'text' => elgg_echo("inbox:send", [strtolower(elgg_echo("item:object:message:$type:singular"))]),
					'href' => elgg_http_add_url_query_elements("messages/compose", [
						'message_type' => $type,
						'send_to' => $recipient->guid
					]),
					'section' => 'action',
				]);
			}
		}

		return $return;
	}

	public static function setupMessageMenu(Hook $hook) {

		$entity = $hook->getEntityParam();
		$menu = $hook->getValue();

		if (!$entity instanceof Message || !$entity->canEdit()) {
			return;
		}

		$threaded = elgg_extract('threaded', $hook->getParams(), false);

		$action_params = [
			'guids' => [$entity->guid],
			'threaded' => $threaded,
		];

		$menu->remove('edit');
		$menu->remove('delete');

		$menu->add(ElggMenuItem::factory([
			'name' => 'forward',
			'icon' => 'fas fa-share',
			'text' => elgg_echo('inbox:forward'),
			'href' => elgg_generate_url('forward:object:messages', [
				'guid' => $entity->guid,
			]),
		]));

		if (!$entity->isPersistent()) {
			$menu->add(ElggMenuItem::factory([
				'name' => 'delete',
				'icon' => 'fas fa-trash',
				'text' => elgg_echo('inbox:delete'),
				'href' => elgg_generate_action_url('inbox/messages/delete', $action_params),
				'data-confirm' => ($threaded) ? elgg_echo('inbox:delete:thread:confirm') : elgg_echo('inbox:delete:message:confirm'),
				'is_action' => true,
				'priority' => 900,
				'link_class' => 'elgg-state elgg-state-danger',
			]));
		}

		return $menu;
	}

	/**
	 * Inbox controls setup
	 *
	 * @param string $hook   "register"
	 * @param string $type   "menu:inbox"
	 * @param array  $return An array of menu items
	 * @param array  $params An array of additional parameters
	 *
	 * @return array An array of menu items
	 */
	public static function setupInboxMenu($hook, $type, $return, $params) {

		$count = elgg_extract('count', $params);

		if ($count) {
			$chkbx = elgg_view('input/checkbox', [
					'id' => 'inbox-form-toggle-all',
				]) . elgg_echo('inbox:form:toggle_all');

			$return[] = ElggMenuItem::factory([
				'name' => 'toggle',
				'text' => elgg_format_element('label', [], $chkbx, ['encode_text' => false]),
				'href' => false,
				'priority' => 50,
				'link_class' => 'elgg-button',
			]);

			if (!elgg_in_context('sent-form')) {
				$return[] = ElggMenuItem::factory([
					'name' => 'markread',
					'text' => elgg_echo('inbox:markread'),
					'href' => 'action/messages/markread',
					'data-submit' => true,
					'priority' => 100,
					'link_class' => 'elgg-button elgg-button-action',
					'item_class' => 'inbox-action hidden',
				]);
				$return[] = ElggMenuItem::factory([
					'name' => 'markunread',
					'text' => elgg_echo('inbox:markunread'),
					'href' => 'action/messages/markunread',
					'link_class' => 'elgg-button elgg-button-action',
					'data-submit' => true,
					'priority' => 200,
					'item_class' => 'inbox-action hidden',
				]);
			}

			$return[] = ElggMenuItem::factory([
				'name' => 'delete',
				'text' => elgg_echo('inbox:delete'),
				'href' => 'action/messages/delete',
				'data-confirm' => elgg_echo('inbox:delete:inbox:confirm'),
				'data-submit' => true,
				'priority' => 300,
				'link_class' => 'elgg-button elgg-button-delete',
				'item_class' => 'inbox-action hidden',
			]);
		}

		return $return;
	}

	public static function setupInboxThreadMenu(Hook $hook) {
		$entity = $hook->getEntityParam();

		if (!$entity instanceof Message || !$entity->canEdit()) {
			return;
		}

		$action_params = [
			'guids' => [$entity->guid],
			'threaded' => true,
		];

		$menu = $hook->getValue();

		$menu->add(ElggMenuItem::factory([
			'name' => 'reply',
			'href' => '#reply',
			'text' => elgg_echo('inbox:reply'),
			'priority' => 100,
			'icon' => 'fas fa-reply',
		]));

		$menu->add(ElggMenuItem::factory([
			'name' => 'markread',
			'href' => elgg_generate_action_url('messages/markread', $action_params),
			'text' => elgg_echo('inbox:markread'),
			'is_action' => true,
			'priority' => 200,
			'icon' => 'fas fa-envelope-open-text',
		]));

		$menu->add(ElggMenuItem::factory([
			'name' => 'markunread',
			'href' => elgg_generate_action_url('messages/markunread', $action_params),
			'text' => elgg_echo('inbox:markunread'),
			'is_action' => true,
			'priority' => 210,
			'icon' => 'fas fa-envelope',
		]));

		if (!$entity->isPersistent()) {
			$menu->add(ElggMenuItem::factory([
				'name' => 'delete',
				'text' => elgg_echo('inbox:delete'),
				'href' => elgg_generate_action_url('messages/delete', $action_params),
				'data-confirm' => elgg_echo('inbox:delete:thread:confirm'),
				'is_action' => true,
				'priority' => 900,
				'link_class' => 'elgg-state elgg-state-danger',
				'icon' => 'fas fa-trash',
			]));
		}

		return $menu;
	}

	public static function setupTopbarMenu(Hook $hook) {
		if (!elgg_is_logged_in()) {
			return;
		}

		$menu = $hook->getValue();

		$count = hypeInbox()->model->countUnreadMessages();
		if ($count > 99) {
			$count = '99+';
		} else if (!$count) {
			$count = null;
		}

		$menu->add(ElggMenuItem::factory([
			'name' => 'inbox',
			'href' => 'messages#inbox-popup',
			'text' => '',
			'icon' => 'fas fa-envelope',
			'badge' => $count,
			'priority' => 600,
			'tooltip' => elgg_echo('inbox:thread:unread', [$count]),
			'rel' => 'popup',
			'id' => 'inbox-popup-link',
			'data-position' => json_encode([
				'my' => 'center top',
				'of' => '.elgg-menu-topbar > .elgg-menu-item-notifications',
				'collision' => 'fit fit',
			]),
		]));

		return $menu;
	}

	public function setupTitleMenu(Hook $hook) {
		if (!elgg_in_context('messages')) {
			return;
		}

		$menu = $hook->getValue();

		$outgoing_message_types = hypeInbox()->model->getOutgoingMessageTypes();

		if (count($outgoing_message_types) === 1) {
			$menu->add(ElggMenuItem::factory([
				'name' => 'compose',
				'text' => elgg_echo('inbox:compose'),
				'href' => elgg_generate_url('add:object:messages', [
					'message_type' => $outgoing_message_types[0],
					'send_to' => get_input('send_to', null),
				]),
				'link_class' => 'elgg-button elgg-button-action',
				'icon' => 'fas fa-plus',
			]));
		} else if (count($outgoing_message_types) > 1) {
			$menu->add(ElggMenuItem::factory([
				'name' => 'compose',
				'text' => elgg_echo('inbox:compose'),
				'href' => false,
				'link_class' => 'elgg-button elgg-button-action',
				'icon' => 'fas fa-plus',
				'child_menu' => [
					'display' => 'dropdown',
					'data-position' => json_encode([
						'at' => 'right bottom',
						'my' => 'right top',
						'collision' => 'fit fit',
					]),
				],
			]));

			foreach ($outgoing_message_types as $mt) {
				$menu->add(ElggMenuItem::factory([
					'name' => ($mt == HYPEINBOX_PRIVATE) ? "send" : "compose:$mt",
					'text' => elgg_echo("item:object:message:$mt:singular"),
					'href' => elgg_generate_url('add:object:messages', [
						'message_type' => $mt,
						'send_to' => get_input('send_to', null),
					]),
					'icon' => 'fas fa-plus',
					'parent_name' => 'compose',
				]));
			}
		}

		return $menu;
	}
}
