<?php

$title = ($entity->title) ? $entity->title : elgg_echo('inbox:untitled');
$summary = elgg_get_excerpt(strip_tags($entity->description), 200);
$desc = elgg_view('output/longtext', [
	'value' => $entity->description,
	'class' => 'inbox-message-body'
]);
$tags = elgg_view('output/tags', [
	'entity' => $entity
]);

echo elgg_view('output/url', [
	'text' => "<b>$title</b> - $summary",
	'href' => $entity->getURL(),
]);
