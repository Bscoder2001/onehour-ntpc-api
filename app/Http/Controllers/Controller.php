<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class Controller
{
    public function sendResponse($message, $status = 200, $data = [])
    {
        $isOk = $status >= 200 && $status < 300;

        return response()->json([
            'isOk' => $isOk,
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function sendEmail($email, $subject, $body)
    {
        try
        {
            Mail::html($body, function ($message) use ($email, $subject)
            {
                $message->to($email)
                        ->subject($subject);
            });

            return $this->sendResponse('Email sent successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('sendEmail failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function generateCredentials($length = 10)
    {
        $username = Str::lower(Str::random(6)) . rand(10, 99);

        $password = Str::random($length);

        return [
            'username' => $username,
            'password' => $password
        ];
    }
}
