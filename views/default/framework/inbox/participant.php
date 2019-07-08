<?php

$entity = elgg_extract('entity', $vars);

if (!$entity instanceof ElggUser) {
	return;
}

$icon = elgg_view_entity_icon($entity, 'tiny');
$link = elgg_view('output/url', [
	'text' => $entity->getDisplayName(),
	'href' => $entity->getURL(),
]);

echo elgg_view_image_block($icon, $link);