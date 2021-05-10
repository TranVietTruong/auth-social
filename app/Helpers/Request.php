<?php

namespace App\Helpers;

/**
 * Request Class helper
 */
class Request
{
	public static function extracUrlParam($url_param){
    	if (!$url_param) return [];

        parse_str($url_param, $param);
        return $param;
    }
}