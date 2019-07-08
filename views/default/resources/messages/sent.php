<?php

use hypeJunction\Inbox\Message;

$page_owner = elgg_get_page_owner_entity();
if (!$page_owner || !$page_owner->canEdit()) {
	forward('', '404');
}

elgg_require_js('framework/inbox/user');

$message_type = get_input('message_type', Message::TYPE_PRIVATE);

$type_label = elgg_echo("item:object:message:$message_type:plural");
$type_url = "messages/sent/$page_owner->username?message_type=$message_type";

elgg_push_breadcrumb(elgg_echo('inbox'), "messages/inbox/$page_owner->username");
elgg_push_breadcrumb(elgg_echo('inbox:message_type:sent', [$type_label]), $type_url);

$layout = elgg_view_layout('default', [
	'title' => elgg_echo('inbox:sent'),
	'filter_id' => 'inbox',
	'filter_context' => "sent-$message_type",
	'content' => elgg_view('framework/inbox/sent', [
		'message_type' => $message_type,
	]),
	'class' => 'inbox-layout',
	'page_menu_params' => ['sort_by' => 'priority'],
	'show_owner_block' => false,
]);

echo elgg_view_page($title, $layout);
