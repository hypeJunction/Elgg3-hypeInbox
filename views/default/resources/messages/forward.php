<?php

use hypeJunction\Inbox\Message;

elgg_gatekeeper();

elgg_import_esm('framework/inbox/user');

$guid = get_input('guid');
$message = get_entity($guid);

if (!$message instanceof Message) {
	throw new \Elgg\Exceptions\Http\PageNotFoundException();
}

$message_type = $message->getMessageType();
$action = 'compose';

$params = hypeInbox()->model->prepareFormValues([], $message_type);
$params['forward'] = $message;

$params['subject'] = "Fwd: $message->title";

$enable_html = elgg_get_plugin_from_id('hypeinbox')->getSetting('enable_html');
if ($enable_html) {
	$params['body'] = '<p>' . elgg_echo('messages:forward:byline', [
		$message->getSender()->getDisplayName(),
		date('H:i j M, Y', $message->time_created),
	]) . '</p>';

	$params['body'] .= '<p><blockquote>' . $message->getBody() . '</blockquote></p>';
} else {
	$params['body'] = PHP_EOL . elgg_echo('messages:forward:byline', [
		$message->getSender()->getDisplayName(),
		date('H:i j M, Y', $message->time_created),
	]) . PHP_EOL . PHP_EOL;
	$lines = explode(PHP_EOL, $message->getBody());
	$lines = array_map(function($elem) {
		return "  >> $elem";
	}, $lines);
	$params['body'] .= implode(PHP_EOL, $lines);
}

$title = elgg_echo("inbox:$action:forward", [elgg_echo("item:object:message:$message_type:singular")]);

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
