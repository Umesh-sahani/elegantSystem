<?php

namespace App\Http\Controllers;

abstract class Controller
{
    // log the real error (storage/logs/laravel.log), send only a simple message to the user
    protected function serverError(\Throwable $e)
    {
        report($e);

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, please try again.',
        ], 500);
    }
}
