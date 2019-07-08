<?php

$guid = elgg_extract('guid', $vars);

elgg_entity_gatekeeper($guid, 'object', 'messages');

$message = get_entity($guid);

elgg_require_js('framework/inbox/user');

$message_type = $message->msgType;
$subject = $message->getDisplayName();

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


elgg_push_breadcrumb(elgg_get_excerpt($subject, 50));

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

	$content = elgg_view_module('aside', null, $thread, [
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

	echo elgg_view_page($title, $layout, 'default', [
		'header' => false,
	]);
}
