<?php

return [
	'bootstrap' => \hypeJunction\Inbox\Bootstrap::class,

	'entities' => [
		[
			'type' => 'object',
			'subtype' => 'messages',
			'class' => \hypeJunction\Inbox\Message::class,
		]
	],

	'actions' => [
		'messages/send' => [],
		'messages/delete' => [],
		'messages/markread' => [],
		'messages/markunread' => [],
		'messages/load' => [],
		'hypeInbox/settings/save' => [
			'access' => 'admin',
		],
		'inbox/admin/import' => [
			'access' => 'admin',
		],
	],

	'routes' => [
		'collection:object:messages:owner' => [
			'path' => '/messages/inbox/{username?}',
			'resource' => 'messages/inbox',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'collection:object:messages:sent' => [
			'path' => '/messages/sent/{username?}',
			'resource' => 'messages/sent',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'collection:object:messages:search' => [
			'path' => '/messages/search',
			'resource' => 'messages/search',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'collection:object:messages:thread' => [
			'path' => '/messages/thread/{hash}',
			'resource' => 'messages/thread',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'add:object:messages' => [
			'path' => '/messages/add/{container_guid?}',
			'resource' => 'messages/compose',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'view:object:messages' => [
			'path' => '/messages/view/{guid}',
			'resource' => 'messages/read',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'read:object:messages' => [
			'path' => '/messages/read/{guid}',
			'resource' => 'messages/read',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'reply:object:messages' => [
			'path' => '/messages/reply/{guid}',
			'resource' => 'messages/read',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'forward:object:messages' => [
			'path' => '/messages/forward/{guid}',
			'resource' => 'messages/forward',
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
			],
		],
		'autocomplete:inbox:guids' => [
			'path' => '/autocomplete/inbox/guids',
			'controller' => \hypeJunction\Inbox\SearchRecipients::class,
			'middleware' => [
				\Elgg\Router\Middleware\Gatekeeper::class,
				\Elgg\Router\Middleware\AjaxGatekeeper::class,
			],
		],
	],
];