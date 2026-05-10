<?php

use hypeJunction\Inbox\Message;

elgg_gatekeeper();

elgg_import_esm('framework/inbox/user');

$guid = get_input('guid');
$message = get_entity($guid);

if ($message instanceof Message) {
	$recipients = $message->getParticipantGuids();
	$message_type = $message->getMessageType();
	$action = 'reply';
	$entity = $message;
} else {
	$recipients = get_input('send_to');
	$message_type = get_input('message_type', Message::TYPE_PRIVATE);
	$action = 'compose';
}

$params = hypeInbox()->model->prepareFormValues($recipients, $message_type, $entity);

$title = elgg_echo("inbox:$action:message_type", [elgg_echo("item:object:message:$message_type:singular")]);

$type_label = elgg_echo("item:object:message:$message_type:plural");
$type_url = "messages/inbox/$page_owner->username?message_type=$message_type";

elgg_register_menu_item('breadcrumbs', \ElggMenuItem::factory([
	'name' => 'bc_1',
	'text' => elgg_echo('inbox'),
	'href' => "messages/inbox/$page_owner->username",
]));
elgg_register_menu_item('breadcrumbs', \ElggMenuItem::factory([
	'name' => 'bc_2',
	'text' => elgg_echo('inbox:message_type', [$type_label]),
	'href' => $type_url,
]));
elgg_register_menu_item('breadcrumbs', \ElggMenuItem::factory([
	'name' => 'bc_3',
	'text' => $title,
	'href' => false,
]));

$layout = elgg_view_layout('content', [
	'title' => $title,
	'filter' => false,
	'content' => elgg_view('framework/inbox/compose', $params),
	'class' => 'inbox-layout inbox-form-layout',
	'page_menu_params' => ['sort_by' => 'priority'],
	'show_owner_block' => false,
]);

echo elgg_view_page($title, $layout);
