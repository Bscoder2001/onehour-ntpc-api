<?php

namespace App\Services\Assessment;

use App\Repositories\Assessment\QuestionRepository;
use App\Services\Assessment\TaxonomyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuestionService
{
    private $questionRepository;
    private $taxonomyService;

    public function __construct(QuestionRepository $questionRepository, TaxonomyService $taxonomyService)
    {
        $this->questionRepository = $questionRepository;
        $this->taxonomyService = $taxonomyService;
    }

    public function createQuestion($data, $userId)
    {
        if (!$this->taxonomyService->validateHierarchy((int) $data['course_id'], (int) $data['subject_id'], (int) $data['chapter_id'], (int) $data['topic_id']))
        {
            throw new \RuntimeException('Invalid course-subject-chapter-topic mapping');
        }

        return DB::transaction(function () use ($data, $userId)
        {
            $payload = [
                'subject_id' => (int) $data['subject_id'],
                'chapter_id' => (int) $data['chapter_id'],
                'topic_id' => (int) $data['topic_id'],
                'question_type' => $data['question_type'],
                'question_text' => trim($data['question_text']),
                'difficulty' => $data['difficulty'],
                'correct_numeric_answer' => isset($data['correct_numeric_answer']) ? $data['correct_numeric_answer'] : null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('questions', 'course_id'))
            {
                $payload['course_id'] = (int) $data['course_id'];
            }

            $questionId = $this->questionRepository->create($payload);

            $this->questionRepository->upsertOptions($questionId, isset($data['options']) ? $data['options'] : []);

            return $this->getQuestionById($questionId);
        });
    }

    public function listQuestions($filters)
    {
        return $this->questionRepository->getList($filters);
    }

    public function getQuestionById($id)
    {
        $question = $this->questionRepository->findById($id);

        if (!$question)
        {
            throw new \RuntimeException('Question not found');
        }

        $options = $this->questionRepository->getOptionsByQuestionId($id);

        return [
            'question' => $question,
            'options' => $options,
        ];
    }

    public function updateQuestion($id, $data, $userId)
    {
        if (!$this->taxonomyService->validateHierarchy((int) $data['course_id'], (int) $data['subject_id'], (int) $data['chapter_id'], (int) $data['topic_id']))
        {
            throw new \RuntimeException('Invalid course-subject-chapter-topic mapping');
        }

        return DB::transaction(function () use ($id, $data, $userId)
        {
            $payload = [
                'subject_id' => (int) $data['subject_id'],
                'chapter_id' => (int) $data['chapter_id'],
                'topic_id' => (int) $data['topic_id'],
                'question_type' => $data['question_type'],
                'question_text' => trim($data['question_text']),
                'difficulty' => $data['difficulty'],
                'correct_numeric_answer' => isset($data['correct_numeric_answer']) ? $data['correct_numeric_answer'] : null,
                'updated_by' => $userId,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('questions', 'course_id'))
            {
                $payload['course_id'] = (int) $data['course_id'];
            }

            $this->questionRepository->updateById($id, $payload);

            $this->questionRepository->upsertOptions($id, isset($data['options']) ? $data['options'] : []);

            return $this->getQuestionById($id);
        });
    }

    public function deleteQuestion($id)
    {
        $this->questionRepository->deleteById($id);
    }
}
