<?php

namespace App\Helpers;

use App\Helpers\RpcClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Auth Class helper
 */
class Auth
{
	private static $user = null;

	public static function checkAuth($route, $action, $body){
		$require_auth = Arr::get($route, 'auth', false);
        $except = Arr::get($route, 'except', []);

        if ($require_auth && !in_array($action, $except)){
        	$header = Arr::get($body, 'headerParam', []);
        	if (!$header){
        		return false;
        	}
        	$header = array_change_key_case($header, CASE_LOWER);

        	$token = Arr::get($header, 'authorization', '');
        	if (Str::startsWith($token, 'Bearer ')) {
	            $token = Str::substr($token, 7);
	        }
	        if (!$token){
	        	return false;
	        }
	        self::setAuth($token);

	        if (!self::user()){
	        	return false;
	        }
        } else {
        	$header = Arr::get($body, 'headerParam', []);
        	$header = array_change_key_case($header, CASE_LOWER);

        	$token = Arr::get($header, 'authorization', '');
        	if (Str::startsWith($token, 'Bearer ')) {
	            $token = Str::substr($token, 7);
	        }
	        if ($token){
	        	self::setAuth($token);
	        }
        }

        return true;
	}

	public static function user(){
		return self::$user;
	}

	public static function setAuth($token){
		$queue = config('rabbitmq.user.rpc.queue');
		$rpc_client = new RpcClient($queue);

		$request = [
			'requestMethod' => 'POST',
			'requestPath' => '/v1.0/user/authentication',
			'urlParam' => '',
			'pathParam' => '',
			'headerParam' => [
				'authorization' => 'Bearer ' . $token
			]
		];

		$response = $rpc_client->call(json_encode($request));
		$user = [];
		try {
			$response = json_decode($response, true);
			if (isset($response['data']['status']) && $response['data']['status'] === 200){
				$user = $response['data']['data'];
			}
		} catch (Exception $e) {
			$user = [];
		}

		self::$user = $user;
	}
}