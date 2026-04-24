<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Services\Assessment\TestService;
use Illuminate\Http\Request;

class TestsController extends Controller
{
    private $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'duration_minutes' => 'required|integer',
        ]);

        $data['description'] = $request->input('description');
        $data['total_marks'] = $request->input('total_marks', 0);
        $data['status'] = $request->input('status', 'draft');

        $result = $this->testService->createTest($data, (int) $request->attributes->get('assessment_user_id'));

        return $this->sendResponse('Test created successfully', 201, $result);
    }

    public function index(Request $request)
    {
        $result = $this->testService->listTests($request->all());

        return $this->sendResponse('Tests fetched successfully', 200, $result);
    }

    public function show($id)
    {
        $result = $this->testService->getTestById($id);

        return $this->sendResponse('Test fetched successfully', 200, $result);
    }

    public function attachQuestions(Request $request, $id)
    {
        $data = $request->validate([
            'question_ids' => 'required|array',
        ]);

        $result = $this->testService->attachQuestions($id, $data['question_ids']);

        return $this->sendResponse('Questions attached to test successfully', 200, $result);
    }

    public function removeQuestion($id, $question_id)
    {
        $result = $this->testService->removeQuestion($id, $question_id);

        return $this->sendResponse('Question removed from test successfully', 200, $result);
    }
}
