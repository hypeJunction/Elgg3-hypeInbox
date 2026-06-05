<?php

use hypeJunction\Inbox\Message;

$limit = get_input('limit', 20);
$offset = get_input('offset', 0);

$ha = access_get_show_hidden_status();
access_show_hidden_entities(true);

$messages = [];
$batch = hypeInbox()->model->getUnhashedMessages([
	'limit' => $limit,
	'offset' => $offset,
]);

foreach ($batch as $message) {
	$messages[] = $message;
}

if (empty($messages)) {
	print json_encode(['complete' => true]);
	return elgg_redirect_response(REFERER);
}

$site = elgg_get_site_entity();

foreach ($messages as $msg) {
	if (!$msg instanceof Message) {
		continue;
	}
	
	$msg->msgHash = $msg->calcHash();

	$msg->msgType = Message::TYPE_PRIVATE;

	elgg_log("Updated message $msg->guid (hash : $msg->msgHash; type : $msg->msgType");

	if (!$msg->save()) {
		$offset++;
	}
}

print json_encode([
	'offset' => $offset
]);

access_show_hidden_entities($ha);

return elgg_redirect_response(REFERER);
