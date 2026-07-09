<?php

use hypeJunction\Inbox\Group;
use hypeJunction\Inbox\Message;

$original_msg_guid = get_input('original_guid');
$original_message = $original_msg_guid ? get_entity((int) $original_msg_guid) : null;

$sender_guid = elgg_get_logged_in_user_guid();
$recipient_guids = Group::create(get_input('recipients', []))->guids();

$subject = htmlspecialchars(get_input('subject', ''), ENT_QUOTES, 'UTF-8');
$body = get_input('body');

if (empty($recipient_guids)) {
	return elgg_error_response(elgg_echo('inbox:send:error:no_recipients'), REFERRER);
}

if (empty(elgg_strip_tags((string) $body))) {
	return elgg_error_response(elgg_echo('inbox:send:error:no_body'), REFERRER);
}

$enable_html = elgg_get_plugin_from_id('hypeinbox')->getSetting('enable_html');
if (!$enable_html) {
	$body = elgg_strip_tags((string) $body);
}

$message_hash = '';
$message_type = get_input('message_type', Message::TYPE_PRIVATE);
if ($original_message instanceof Message) {
	$message_hash = $original_message->getHash();
	$message_type = $original_message->getMessageType();
}

$message = Message::factory([
	'sender' => $sender_guid,
	'recipients' => $recipient_guids,
	'subject' => $subject,
	'body' => $body,
	'hash' => $message_hash,
	'message_type' => $message_type,
]);

$guid = $message->send();

if (!$guid) {
	return elgg_error_response(elgg_echo('inbox:send:error:generic'), REFERRER);
}

$new_message = get_entity((int) $guid);

$sender = $new_message->getSender();
$message_type = $new_message->getMessageType();
$message_hash = $new_message->getHash();

$ruleset = hypeInbox()->config->getRuleset($message_type);

$recipients = $new_message->getRecipients();

foreach ($recipients as $recipient) {
	if ($recipient->guid == $sender->guid) {
		continue;
	}

	$type_label = strtolower($ruleset->getSingularLabel($recipient->language));

	$subject = elgg_echo('inbox:notification:subject', [$type_label], $recipient->language);
	$notification = elgg_echo('inbox:notification:body', [
		$type_label,
		$sender->name,
		$body,
		elgg_view('output/url', [
			'href' => $new_message->getURL(),
		]),
		$sender->name,
		elgg_view('output/url', [
			'href' => elgg_normalize_url("messages/thread/$message_hash#reply")
		]),
	], $recipient->language);
	
	elgg_notify_user($recipient, 'send', $new_message, [
		'subject' => $subject,
		'body' => $notification,
		'attachments' => $attachments ?? [],
		'template' => 'messages_send',
		'action' => 'send',
		'object' => $new_message,
		'recipients' => $recipients,
	], $sender);
}

return elgg_ok_response('', elgg_echo('inbox:send:success'), $new_message->getURL());
