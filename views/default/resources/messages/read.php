<?php

$guid = (int) elgg_extract('guid', $vars);

$message = elgg_entity_gatekeeper($guid, 'object', 'messages');

elgg_import_esm('framework/inbox/user');

$message_type = $message->msgType;
$subject = $message->getDisplayName();
$page_owner = elgg_get_logged_in_user_entity();

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


elgg_register_menu_item('breadcrumbs', \ElggMenuItem::factory([
	'name' => 'bc_3',
	'text' => elgg_get_excerpt($subject, 50),
	'href' => false,
]));

$params = [
	'entity' => $message,
	'message_type' => $message_type,
];

$thread = elgg_view('framework/inbox/thread', $params);

if (elgg_is_xhr()) {
	echo $thread;
} else {
	$participants = elgg_view('framework/inbox/participants', $params);
	$menu = elgg_view('framework/inbox/menu', $params);

	$reply = elgg_view('framework/inbox/reply', $params);

	$content = elgg_view_module('aside', '', $thread, [
		'footer' => $reply,
		'class' => 'inbox-message-block inbox-module has-list',
	]);

	$layout = elgg_view_layout('default', [
		'title' => $subject,
		'filter' => false,
		'content' => $content,
		'sidebar' => $menu . $participants,
		'class' => 'inbox-layout inbox-thread-layout',
		'page_menu_params' => ['entity' => $message, 'sort_by' => 'priority'],
		'show_owner_block' => false,
	]);

	$title = $subject;
	echo elgg_view_page($title, $layout, 'default', [
		'header' => false,
	]);
}
