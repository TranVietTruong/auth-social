<?php

namespace App\Helpers;

use App\Helpers\Auth;
use App\Helpers\Request;
use App\Helpers\Response;
use Illuminate\Support\Arr;

/**
 * Route Class helper
 */
class Route
{
	private $route;

	public function __construct($route){
		$this->route = $route;
	}

	/**
	 * @param  AMQP request $body
	 * @return App\Helpers\Response
	 */
	public function response($body){
		$controller = '';
		$action = '';
        $parameters = [$body];
        // get request method
        $request_method = strtolower(Arr::get($body, 'requestMethod', ''));
        if (!$this->allowRequestMethod($request_method)){
        	return Response::dataError('Method not allow');
        }

		if (strtolower($this->route['method']) === 'resource'){
			$controller = 'App\Http\Controllers\\' . $this->route['action'];

	        if ($request_method === 'get'){
	        	$id = Arr::get($body, 'pathParam', '');
	            if ($id){
	                // get by id
	                $action = 'show';
	                $parameters = [$id];
	            }else{
	                // get list
	                $action = 'list';
	                $url_param = Arr::get($body, 'urlParam', []);
	                $request = $url_param ? Request::extracUrlParam($url_param) : [];
	                $parameters = [$request];
	            }
	        }
	        // method post
	        if ($request_method === 'post'){
	        	$action = 'store';
	        	$request = Arr::get($body, 'bodyParam', []);
	            $parameters = [$request];
	        }
	        // method put
	        if ($request_method === 'put'){
	        	$action = 'update';
	            $id = Arr::get($body, 'pathParam', '');
	            $request = Arr::get($body, 'bodyParam', []);

	            if (!$id){
	                return Response::dataError('Method not allow');
	            }
	            $parameters = [$id, $request];
	        }
	        // method delete
	        if ($request_method === 'delete'){
	        	$action = 'destroy';
	            $id = Arr::get($body, 'pathParam', '');
	            if (!$id){
	                return Response::dataError('Method not allow');
	            }
	            $parameters = [$id];
	        }
		}else{
			if ($request_method !== strtolower($this->route['method'])){
				return Response::dataError('Method not allow');
			}

			$route_arr = explode('@', $this->route['action']);

			if (count($route_arr) !== 2){
				return Response::dataError('API Not Found', 404);
			}

			$controller = 'App\Http\Controllers\\' . $route_arr[0];
			$action = $route_arr[1];
			if ($request_method === 'get'){
				$url_param = Arr::get($body, 'urlParam', []);
				$request = $url_param ? Request::extracUrlParam($url_param) : [];
	            $parameters = [$request];
			}else{
				$request = Arr::get($body, 'bodyParam', []);
	            $parameters = [$request];
			}
		}

		if (!$controller || !$action){
			return Response::dataError('API Not Found', 404);
		}

		if (!class_exists($controller)){
        	return Response::dataError('Class ' . $controller . ' Not Found', 500);
        }

        if (!method_exists($controller, $action)){
        	return Response::dataError('Action ' . $action . ' Not Found', 500);
        }

        // check auth
//        if (!Auth::checkAuth($this->route, $action, $body)){
//        	return Response::dataError('Unauthorized', 401);
//        }
        try {
        	return call_user_func_array([new $controller, $action], $parameters);
        } catch (\Throwable $e){
        	return config('app.env') === 'production' ? Response::dataError('Server Error.', 500) : Response::dataError($e->getMessage(), 500);
        } catch (\Exception $e){
        	return config('app.env') === 'production' ? Response::dataError('Server Error.', 500) : Response::dataError($e->getMessage(), 500);
        }
	}

	private function allowRequestMethod($request_method){
		return in_array(strtolower($request_method), [
			'get',
			'post',
			'put',
			'patch',
			'delete'
		]);
	}
}
