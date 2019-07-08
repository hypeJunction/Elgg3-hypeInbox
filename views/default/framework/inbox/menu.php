<?php

$entity = elgg_extract('entity', $vars);

$menu = elgg_view_menu('inbox:thread', [
	'entity' => $entity,
	'class' => 'elgg-menu-hover',
]);

echo elgg_view_module('aside', null, $menu, [
	'class' => 'inbox-module has-list',
]);