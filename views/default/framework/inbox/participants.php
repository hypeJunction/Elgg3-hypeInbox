<?php

use hypeJunction\Inbox\Message;

$entity = elgg_extract('entity', $vars, false);
if (!$entity instanceof Message) {
	return true;
}

$title = elgg_echo('inbox:thread:participants');
$body = elgg_view_entity_list($entity->getParticipants(), [
	'full_view' => false,
	'size' => 'small',
	'limit' => 0,
	'pagination' => false,
	'item_view' => 'framework/inbox/participant',
]);

echo elgg_view_module('aside', $title, $body, [
	'class' => 'inbox-module has-list',
]);