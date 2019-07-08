<?php

use hypeJunction\Inbox\Message;

$guids = get_input('guids', []);
$threaded = get_input('threaded');

if (!is_array($guids) || empty($guids)) {
	return elgg_error_response(elgg_echo('inbox:markread:error'));
}

$count = count($guids);
$success = $notfound = 0;

foreach ($guids as $guid) {
	$message = get_entity($guid);
	if (!$message instanceof Message) {
		$notfound++;
		continue;
	}
	$message->markRead($threaded);
	$success++;
}

if ($count > 1) {
	$msg[] = elgg_echo('inbox:markread:success', [$success]);
	if ($notfound > 0) {
		$msg[] = elgg_echo('inbox:error:notfound', [$notfound]);
	}
} else if ($success) {
	$msg[] = elgg_echo('inbox:markread:success:single');
} else {
	$msg[] = elgg_echo('inbox:markread:error');
}

$msg = implode('<br />', $msg);

if ($success < $count) {
	return elgg_error_response($msg);
}

return elgg_ok_response('', $msg);