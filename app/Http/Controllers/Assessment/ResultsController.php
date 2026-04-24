<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Services\Assessment\AttemptService;

class ResultsController extends Controller
{
    private $attemptService;

    public function __construct(AttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }

    public function show($attempt_id)
    {
        $result = $this->attemptService->resultByAttempt($attempt_id);

        return $this->sendResponse('Result fetched successfully', 200, $result);
    }

    public function byUser($user_id)
    {
        $result = $this->attemptService->resultByUser($user_id);

        return $this->sendResponse('User results fetched successfully', 200, $result);
    }
}
