<?php

use hypeJunction\Inbox\Message;

$guids = get_input('guids', []);
$threaded = get_input('threaded');

if (!is_array($guids) || empty($guids)) {
	elgg_register_error_message(elgg_echo('inbox:delete:error'));
	elgg_redirect_response(REFERRER);
}

$count = count($guids);
$error = 0;
$success = 0;
$persistent = 0;
$notfound = 0;

foreach ($guids as $guid) {
	$message = get_entity($guid);
	if (!$message instanceof Message) {
		$notfound++;
		continue;
	}

	if ($message->isPersistent()) {
		$persistent++;
		continue;
	}

	if ($threaded) {
			$deleted = $message->thread()->delete(true);
	} else {
		$deleted = $message->delete(true);
	}

	if (!$deleted) {
		$error++;
	} else {
		$success++;
	}
}

if ($count > 1) {
	$msg[] = elgg_echo('inbox:delete:success', [$success]);
	if ($notfound > 0) {
		$msg[] = elgg_echo('inbox:error:notfound', [$notfound]);
	}

	if ($persistent > 0) {
		$msg[] = elgg_echo('inbox:error:canedit', [$persistent]);
	}

	if ($error > 0) {
		$msg[] = elgg_echo('inbox:error:unknown', [$error]);
	}

	$forward = REFERRER;
} else if ($success) {
	$msg[] = elgg_echo('inbox:delete:success:single');
	$forward = 'messages';
} else {
	$msg[] = elgg_echo('inbox:delete:error');
	$forward = REFERRER;
}

$msg = implode('<br />', $msg);
if ($success < $count) {
	elgg_register_error_message($msg);
} else {
	elgg_register_success_message($msg);
}

elgg_redirect_response($forward);
