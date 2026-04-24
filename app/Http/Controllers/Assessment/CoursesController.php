<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Services\Assessment\CourseService;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
    private $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $data['status'] = $request->input('status', 'active');

        $result = $this->courseService->createCourse($data, (int) $request->attributes->get('assessment_user_id'));

        return $this->sendResponse('Course created successfully', 201, $result);
    }

    public function index()
    {
        $result = $this->courseService->listCourses();

        return $this->sendResponse('Courses fetched successfully', 200, $result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $data['status'] = $request->input('status');

        $result = $this->courseService->updateCourse((int) $id, $data, (int) $request->attributes->get('assessment_user_id'));

        return $this->sendResponse('Course updated successfully', 200, $result);
    }

    public function destroy($id)
    {
        $this->courseService->deactivateOrDeleteCourse((int) $id);

        return $this->sendResponse('Course updated successfully', 200, []);
    }
}
