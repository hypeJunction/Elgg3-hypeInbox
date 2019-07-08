<?php

use hypeJunction\Inbox\Message;

$page_owner = elgg_get_page_owner_entity();

if (!$page_owner instanceof ElggUser || !$page_owner->canEdit()) {
	throw new \Elgg\EntityNotFoundException();
}

$message_type = get_input('message_type', Message::TYPE_PRIVATE);

elgg_push_breadcrumb(
	elgg_echo('inbox'),
	elgg_generate_url('collection:object:messages:owner', [
		'type' => 'inbox',
		'username' => $page_owner->username,
	])
);

elgg_push_breadcrumb(
	elgg_echo('inbox:message_type', [
		elgg_echo("item:object:message:$message_type:plural")
	]),
	elgg_generate_url('collection:object:messages:owner', [
		'type' => 'inbox',
		'username' => $page_owner->username,
		'message_type' => $message_type,
	])
);

elgg_require_js('framework/inbox/user');

$content = elgg_view('framework/inbox/inbox', [
	'message_type' => $message_type
]);

if (elgg_is_xhr()) {
	echo $content;
} else {
	$layout = elgg_view_layout('default', [
		'title' => elgg_echo('inbox:inbox'),
		'filter_id' => 'inbox',
		'filter_context' => "inbox-$message_type",
		'content' => $content,
		'class' => 'inbox-layout',
		'page_menu_params' => ['sort_by' => 'priority'],
		'show_owner_block' => false,
	]);

	echo elgg_view_page($title, $layout);
}
