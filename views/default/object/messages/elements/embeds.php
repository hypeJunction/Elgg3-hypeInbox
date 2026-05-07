<?php

use hypeJunction\Inbox\Message;

$entity = elgg_extract('entity', $vars);
/* @var $entity Message */

$full = elgg_extract('full_view', $vars, false);
if (!$full) {
	return true;
}

$qualifiers = elgg_trigger_event_results('extract:qualifiers', 'messages', ['source' => $entity->getBody()], []);

if (!empty($qualifiers['urls'])) {
	foreach ($qualifiers['urls'] as $url) {
		echo elgg_trigger_event_results('format:src', 'embed', [
			'src' => $url,
		], '');
	}
}
