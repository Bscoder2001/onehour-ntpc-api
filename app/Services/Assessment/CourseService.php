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

    /**
     * DB column `courses.status` is an integer; API uses 'active' / 'inactive'.
     */
    private function statusToDb(mixed $status): int
    {
        if ($status === 0 || $status === '0' || $status === false)
        {
            return 0;
        }

        if (is_int($status))
        {
            return $status === 0 ? 0 : 1;
        }

        if (is_string($status) && is_numeric($status))
        {
            return ((int) $status) === 0 ? 0 : 1;
        }

        $s = strtolower((string) $status);

        return in_array($s, ['inactive', 'off', '0'], true) ? 0 : 1;
    }

    private function statusToApi(mixed $value): string
    {
        if (is_string($value) && in_array($value, ['active', 'inactive'], true))
        {
            return $value;
        }

        $n = is_numeric($value) ? (int) $value : 1;

        return $n === 0 ? 'inactive' : 'active';
    }

    private function withApiStatus(mixed $row)
    {
        if ($row === null)
        {
            return null;
        }

        if (isset($row->status))
        {
            $row->status = $this->statusToApi($row->status);
        }

        return $row;
    }

    public function createCourse($data, $userId)
    {
        $courseId = $this->courseRepository->create([
            'name' => trim($data['name']),
            'status' => $this->statusToDb($data['status'] ?? 'active'),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->withApiStatus($this->courseRepository->findById($courseId));
    }

    public function listCourses()
    {
        $rows = $this->courseRepository->listAll();

        return $rows->map(function ($row)
        {
            return $this->withApiStatus($row);
        });
    }

    public function updateCourse($id, $data, $userId)
    {
        $payload = [
            'name' => trim($data['name']),
            'updated_at' => now(),
        ];

        if (isset($data['status']))
        {
            $payload['status'] = $this->statusToDb($data['status']);
        }

        if ($userId > 0)
        {
            $payload['updated_by'] = $userId;
        }

        $this->courseRepository->update($id, $payload);

        return $this->withApiStatus($this->courseRepository->findById($id));
    }

    public function deactivateOrDeleteCourse($id)
    {
        $this->courseRepository->deactivateOrDelete($id);
    }
}
