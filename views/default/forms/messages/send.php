<?php

/**
 * Compose message form
 */
$original_message = elgg_extract('entity', $vars, false);
$message_type = elgg_extract('message_type', $vars);
$recipient_guids = elgg_extract('recipient_guids', $vars, []);
$subject = elgg_extract('subject', $vars, '');
$message = elgg_extract('body', $vars, '');
$multiple = elgg_extract('multiple', $vars, false);
$has_subject = elgg_extract('has_subject', $vars, true);
$allows_attachments = elgg_extract('allows_attachments', $vars, false);

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'message_type',
	'value' => $message_type,
]);

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'original_guid',
	'value' => $original_message->guid,
]);

if (!$original_message) {
	$label = function() use ($multiple) {
		if ($multiple) {
			return elgg_echo('inbox:message:recipients');
		}

		return elgg_echo('inbox:message:recipient');
	};

	echo elgg_view_field([
		'#type' => 'guids',
		'#label' => $label(),
		'name' => 'recipients',
		'value' => $recipient_guids,
		'multiple' => $multiple,
		'source' => elgg_generate_url('autocomplete:inbox:guids'),
		'options' => [
			'message_type' => $message_type,
		],
	]);
} else {
	foreach ($recipient_guids as $guid) {
		echo elgg_view_field([
			'#type' => 'hidden',
			'name' => 'recipients[]',
			'value' => $guid,
		]);
	}
}

if ($has_subject) {
	if (!$original_message) {
		echo elgg_view_input('text', [
			'name' => 'subject',
			'value' => $subject,
			'label' => elgg_echo('inbox:message:subject'),
		]);
	} else {
		$subject = $original_message->getReplySubject();
		echo elgg_view_input('hidden', [
			'name' => 'subject',
			'value' => $subject,
		]);
	}
}

echo elgg_view_field([
	'#type' => 'inbox/message',
	'#label' => ($original_message) ? '' : elgg_echo('inbox:message:body'),
	'name' => 'body',
	'value' => $message,
	'rows' => 5,
]);

echo elgg_view('forms/messages/send/extend', $vars);

if ($allows_attachments) {
	echo elgg_view_field([
		'#type' => 'attachments',
		'name' => 'message_attachments',
		'expand' => false,
		'field_class' => 'clearfix',
	]);
}

$footer = elgg_view_field([
	'#type' => 'submit',
	'value' => elgg_echo('inbox:message:send'),
	'field_class' => 'elgg-foot',
]);

elgg_set_form_footer($footer);
