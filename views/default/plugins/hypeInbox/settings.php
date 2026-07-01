<?php

$entity = elgg_extract('entity', $vars);

$messages = elgg_call(ELGG_SHOW_DISABLED_ENTITIES, function () {
	return hypeInbox()->model->getUnhashedMessages(['count' => true]);
});

if ($messages) {
	echo elgg_view('framework/inbox/admin/import', [
		'count' => $messages
	]);
}

echo elgg_view_input('select', [
	'name' => 'params[enable_html]',
	'value' => $entity->enable_html,
	'options_values' => [
		0 => elgg_echo('option:no'),
		1 => elgg_echo('option:yes'),
	],
	'label' => elgg_echo('inbox:settings:enable_html'),
	'help' => elgg_echo('inbox:settings:enable_html:help'),
]);

