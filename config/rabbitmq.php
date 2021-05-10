<?php

return [
	// rabbitmq connection
	'connection' => [
		'host' => env('RABBITMQ_HOST', 'localhost'),
		'port' => env('RABBITMQ_PORT', 5672),
		'user' => env('RABBITMQ_USER', 'guest'),
		'password' => env('RABBITMQ_PASSWORD', 'guest'),
		'vhost' => env('RABBITMQ_VHOST', '/'),
		'consumer_tag' => env('RABBITMQ_CONSUMER_TAG', 'consumer'),
	],

	// rabbitmq service
	'auth' => [
		'routes' => [
            '/v1.0/auth/url' => [
                'method' => 'post',
                'action' => 'AuthController@url',
                'auth' => false
            ],
			'/v1.0/auth/user' => [
				'method' => 'get',
				'action' => 'AuthController@user',
				'auth' => false
			],
            '/v1.0/auth/authentication' => [
                'method' => 'post',
                'action' => 'AuthController@authentication',
                'auth' => false
            ],
            '/v1.0/auth/logout' => [
                'method' => 'post',
                'action' => 'AuthController@logout',
                'auth' => false
            ],
            '/v1.0/auth/refresh' => [
                'method' => 'post',
                'action' => 'AuthController@refresh',
                'auth' => false
            ],
		],
		'rpc' => [
			'key' => 'auth_social_rpc',
			'queue' => 'auth_social_rpc_queue',
			'exchange' => 'auth_social_rpc_exchange'
		]
	]
];
