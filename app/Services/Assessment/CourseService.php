<?php

namespace App\Services\Assessment;

use App\Repositories\Assessment\CourseRepository;

class CourseService
{
    private $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    public function createCourse($data, $userId)
    {
        $courseId = $this->courseRepository->create([
            'name' => trim($data['name']),
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->courseRepository->findById($courseId);
    }

    public function listCourses()
    {
        return $this->courseRepository->listAll();
    }

    public function updateCourse($id, $data, $userId)
    {
        $payload = [
            'name' => trim($data['name']),
            'updated_at' => now(),
        ];

        if (isset($data['status']))
        {
            $payload['status'] = $data['status'];
        }

        if ($userId > 0)
        {
            $payload['updated_by'] = $userId;
        }

        $this->courseRepository->update($id, $payload);

        return $this->courseRepository->findById($id);
    }

    public function deactivateOrDeleteCourse($id)
    {
        $this->courseRepository->deactivateOrDelete($id);
    }
}
