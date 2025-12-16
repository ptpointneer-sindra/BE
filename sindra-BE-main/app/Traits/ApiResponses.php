<?php

namespace App\Traits;

trait ApiResponses
{


    public function sendSuccessResponse(array $data, $mesasage, $statusCode, $otherData = null)
    {
        return response()->json([
            'success' => true,
            'message' => $mesasage,
            'data' => $data,
            'other' => $otherData
        ], $statusCode);
    }


    public function sendErrorResponse($mesasage, $statusCode)
    {
        return response()->json([
            'error' => true,
            'message' => $mesasage,
        ], $statusCode);
    }

    public function responseSuccess(array $data, $message, $statusCode)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function responseError($message, $statusCode,$errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
}