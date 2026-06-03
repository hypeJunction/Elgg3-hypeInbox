<?php

use hypeJunction\Inbox\Message;

$guids = get_input('guids', []);
$threaded = get_input('threaded');

if (!is_array($guids) || empty($guids)) {
	elgg_register_error_message(elgg_echo('inbox:markunread:error'));
	elgg_redirect_response(REFERRER);
}

$count = count($guids);
$success = 0;
$notfound = 0;

foreach ($guids as $guid) {
	$message = get_entity($guid);
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
	elgg_register_error_message($msg);
} else {
	elgg_register_success_message($msg);
}
