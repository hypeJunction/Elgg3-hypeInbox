<?php

use hypeJunction\Inbox\Message;

$guids = get_input('guids', []);
$threaded = get_input('threaded');

if (!is_array($guids) || empty($guids)) {
	return elgg_error_response(elgg_echo('inbox:markunread:error'), REFERRER);
}

$count = count($guids);
$success = 0;
$notfound = 0;

foreach ($guids as $guid) {
	$message = get_entity((int) $guid);
	if (!$message instanceof Message) {
		$notfound++;
		continue;
	}

	$message->markUnread($threaded);
	$success++;
}

if ($count > 1) {
	$msg[] = elgg_echo('inbox:markunread:success', [$success]);
	if ($notfound > 0) {
		$msg[] = elgg_echo('inbox:error:notfound', [$notfound]);
	}
} else if ($success) {
	$msg[] = elgg_echo('inbox:markunread:success:single');
} else {
	$msg[] = elgg_echo('inbox:markunread:error');
}


$msg = implode('<br />', $msg);
if ($success < $count) {
	return elgg_error_response($msg);
}

return elgg_ok_response('', $msg);
