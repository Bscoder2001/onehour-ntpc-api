<?php

namespace App\Services\Assessment;

use App\Repositories\Assessment\TestRepository;

class TestService
{
    private $testRepository;

    public function __construct(TestRepository $testRepository)
    {
        $this->testRepository = $testRepository;
    }

    public function createTest($data, $userId)
    {
        $testId = $this->testRepository->create([
            'title' => trim($data['title']),
            'description' => isset($data['description']) ? $data['description'] : null,
            'duration_minutes' => (int) $data['duration_minutes'],
            'total_marks' => (int) (isset($data['total_marks']) ? $data['total_marks'] : 0),
            'status' => isset($data['status']) ? $data['status'] : 'draft',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->getTestById($testId);
    }

    public function listTests($filters)
    {
        return $this->testRepository->getList($filters);
    }

    public function getTestById($id)
    {
        $test = $this->testRepository->findById($id);

        if (!$test)
        {
            throw new \RuntimeException('Test not found');
        }

        $questions = $this->testRepository->listQuestions($id);

        return [
            'test' => $test,
            'questions' => $questions,
        ];
    }

    public function attachQuestions($testId, $questionIds)
    {
        $this->testRepository->attachQuestions($testId, $questionIds);

        return $this->getTestById($testId);
    }

    public function removeQuestion($testId, $questionId)
    {
        $this->testRepository->removeQuestion($testId, $questionId);

        return $this->getTestById($testId);
    }
}
