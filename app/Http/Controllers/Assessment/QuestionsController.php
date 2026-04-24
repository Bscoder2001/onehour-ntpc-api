<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Services\Assessment\QuestionService;
use Illuminate\Http\Request;

class QuestionsController extends Controller
{
    private $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'chapter_id' => 'required|integer',
            'topic_id' => 'required|integer',
            'question_type' => 'required|string',
            'question_text' => 'required|string',
            'difficulty' => 'required|string',
        ]);

        $data['correct_numeric_answer'] = $request->input('correct_numeric_answer');
        $data['options'] = $request->input('options', []);

        $result = $this->questionService->createQuestion($data, (int) $request->attributes->get('assessment_user_id'));

        return $this->sendResponse('Question created successfully', 201, $result);
    }

    public function index(Request $request)
    {
        $result = $this->questionService->listQuestions($request->all());

        return $this->sendResponse('Questions fetched successfully', 200, $result);
    }

    public function show($id)
    {
        $result = $this->questionService->getQuestionById($id);

        return $this->sendResponse('Question fetched successfully', 200, $result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'course_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'chapter_id' => 'required|integer',
            'topic_id' => 'required|integer',
            'question_type' => 'required|string',
            'question_text' => 'required|string',
            'difficulty' => 'required|string',
        ]);

        $data['correct_numeric_answer'] = $request->input('correct_numeric_answer');
        $data['options'] = $request->input('options', []);

        $result = $this->questionService->updateQuestion($id, $data, (int) $request->attributes->get('assessment_user_id'));

        return $this->sendResponse('Question updated successfully', 200, $result);
    }

    public function destroy($id)
    {
        $this->questionService->deleteQuestion($id);

        return $this->sendResponse('Question deleted successfully', 200, []);
    }
}
