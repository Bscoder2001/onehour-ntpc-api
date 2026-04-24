<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Services\Assessment\AttemptService;
use Illuminate\Http\Request;

class AttemptsController extends Controller
{
    private $attemptService;

    public function __construct(AttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }

    public function start(Request $request, $id)
    {
        $result = $this->attemptService->startAttempt($id, (int) $request->attributes->get('assessment_user_id'));

        return $this->sendResponse('Attempt started successfully', 201, $result);
    }

    public function answer(Request $request, $id)
    {
        $data = $request->validate([
            'question_id' => 'required|integer',
        ]);

        $data['selected_option_id'] = $request->input('selected_option_id');
        $data['numeric_answer'] = $request->input('numeric_answer');

        $result = $this->attemptService->saveAnswer($id, $data);

        return $this->sendResponse('Answer saved successfully', 200, $result);
    }

    public function submit($id)
    {
        $result = $this->attemptService->submitAttempt($id);

        return $this->sendResponse('Attempt submitted successfully', 200, $result);
    }
}
