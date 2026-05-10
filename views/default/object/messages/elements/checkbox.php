<?php

$entity = elgg_extract('entity', $vars);

echo elgg_view('input/checkbox', [
	'name' => 'guids[]',
	'default' => false,
	'value' => $entity->guid,
]);
