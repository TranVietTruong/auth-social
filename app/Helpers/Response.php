<?php

namespace App\Helpers;

/**
 * Response Class helper
 */
class Response
{
	public static function data($data=[], $total=0, $message='Successfully', $status=200){
    	return [
            'status' => $status,
            'message' => $message,
            'data' => [
                'status' => $status,
                'message' => $message,
                'data' => $data,
                'total' => $total
            ]
        ];
    }

    public static function dataError($message='Forbidden', $status=403){
        return self::data([], 0, $message, $status);
    }
}