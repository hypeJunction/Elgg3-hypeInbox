<?php

use hypeJunction\Inbox\Message;

$limit = get_input('limit', 20);
$offset = get_input('offset', 0);

$messages = elgg_call(ELGG_SHOW_DISABLED_ENTITIES, function () use ($limit, $offset) {
	$found = [];
	$batch = hypeInbox()->model->getUnhashedMessages([
		'limit' => $limit,
		'offset' => $offset,
	]);

	foreach ($batch as $message) {
		$found[] = $message;
	}

	return $found;
});

if (empty($messages)) {
	print json_encode(['complete' => true]);
	return elgg_redirect_response(REFERRER);
}

$site = elgg_get_site_entity();

elgg_call(ELGG_SHOW_DISABLED_ENTITIES, function () use ($messages, &$offset) {
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
});

print json_encode([
	'offset' => $offset
]);

return elgg_redirect_response(REFERRER);
