<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Services\Assessment\TaxonomyService;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    private $taxonomyService;

    public function __construct(TaxonomyService $taxonomyService)
    {
        $this->taxonomyService = $taxonomyService;
    }

    public function subjects(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|integer',
        ]);

        $result = $this->taxonomyService->listSubjects((int) $data['course_id']);

        return $this->sendResponse('Subjects fetched successfully', 200, $result);
    }

    public function chapters(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|integer',
        ]);

        $result = $this->taxonomyService->listChapters((int) $data['subject_id']);

        return $this->sendResponse('Chapters fetched successfully', 200, $result);
    }

    public function topics(Request $request)
    {
        $data = $request->validate([
            'chapter_id' => 'required|integer',
        ]);

        $result = $this->taxonomyService->listTopics((int) $data['chapter_id']);

        return $this->sendResponse('Topics fetched successfully', 200, $result);
    }
}
