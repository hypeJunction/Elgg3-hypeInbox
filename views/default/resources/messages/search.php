<?php

$page_owner = elgg_get_page_owner_entity();

if (!$page_owner || !$page_owner->canEdit()) {
	throw new \Elgg\EntityNotFoundException();
}

elgg_require_js('framework/inbox/user');

elgg_push_breadcrumb(
	elgg_echo('inbox'),
	elgg_generate_url('collection:object:messages:owner', [
		'type' => 'inbox',
		'username' => $page_owner->username,
	])
);

$content = elgg_view('framework/inbox/search');

if (elgg_is_xhr()) {
	echo $content;
} else {
	$layout = elgg_view_layout('default', [
		'title' => elgg_echo('inbox:search'),
		'filter_id' => 'inbox',
		'filter_context' => 'search',
		'content' => $content,
		'class' => 'inbox-layout',
		'page_menu_params' => ['sort_by' => 'priority'],
		'show_owner_block' => false,
	]);
	echo elgg_view_page($title, $layout);
}
