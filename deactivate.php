<?php

use hypeJunction\Inbox\Message;

$subtypes = [Message::SUBTYPE];

foreach ($subtypes as $subtype) {
	update_subtype('object', $subtype);
}
