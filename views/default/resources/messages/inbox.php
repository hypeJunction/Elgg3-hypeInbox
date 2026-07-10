<?php

use hypeJunction\Inbox\Message;

// The route declares {username?}. When it is omitted — which is exactly what
// Menus::topbar generates for the inbox link — Elgg leaves the page owner
// unset, and this resource used to throw. Default to the logged-in user, the
// Elgg convention for an optional owner segment.
$page_owner = elgg_get_page_owner_entity() ?: elgg_get_logged_in_user_entity();

if (!$page_owner instanceof ElggUser || !$page_owner->canEdit()) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException();
}

$message_type = get_input('message_type', Message::TYPE_PRIVATE);

elgg_register_menu_item('breadcrumbs', \ElggMenuItem::factory([
	'name' => 'bc_1',
	'text' => elgg_echo('inbox'),
	'href' => elgg_generate_url('collection:object:messages:owner', [
		'type' => 'inbox',
		'username' => $page_owner->username,
	]),
]));

elgg_register_menu_item('breadcrumbs', \ElggMenuItem::factory([
	'name' => 'bc_2',
	'text' => elgg_echo('inbox:message_type', [
		elgg_echo("item:object:message:$message_type:plural")
	]),
	'href' => elgg_generate_url('collection:object:messages:owner', [
		'type' => 'inbox',
		'username' => $page_owner->username,
		'message_type' => $message_type,
	]),
]));

elgg_import_esm('framework/inbox/user');

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

	$title = elgg_echo('inbox:inbox');
	echo elgg_view_page($title, $layout);
}
