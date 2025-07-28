<?php

namespace App\Helpers;

use Illuminate\Validation\ValidationException;
use Throwable;

class ResponseHelper
{
    public static function success($message, $data = NULL, $code = 201)
    {
        if ($data === NULL) {
            return response()->json([
                'status' => 'success',
                'code' => $code,
                'meta_data' => [
                    'code' => $code,
                    'message' => $message,
                ],

            ], $code);
        } else{
            return response()->json([
                'status' => 'success',
                'code' => $code,
                'data' => $data,
                'meta_data' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], $code);
        }
    }

    public static function error(Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'meta_data' => [
                    'code' => 422,
                    'message' => 'Validation errors occurred.',
                    'errors' => $e->validator->errors()->toArray(),
                ],
            ], 422);
        } else{
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'An error occurred while processing your request.',
                    'errors' => $e->getMessage(),
                ],
            ], 500);
        }

       
    }
}
