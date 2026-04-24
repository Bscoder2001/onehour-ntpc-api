<?php

namespace App\Repositories\Assessment;

use Illuminate\Support\Facades\DB;

class TestRepository
{
    public function create($payload)
    {
        return (int) DB::table('tests')->insertGetId($payload);
    }

    public function getList($filters)
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;

        return DB::table('tests')
            ->select('id', 'title', 'duration_minutes', 'total_marks', 'status', 'created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findById($id)
    {
        return DB::table('tests')->where('id', $id)->first();
    }

    public function attachQuestions($testId, $questionIds)
    {
        $existingCount = (int) DB::table('test_questions')->where('test_id', $testId)->count();
        $insertRows = [];

        foreach ($questionIds as $index => $questionId)
        {
            $insertRows[] = [
                'test_id' => $testId,
                'question_id' => (int) $questionId,
                'question_order' => $existingCount + $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertRows))
        {
            DB::table('test_questions')->insert($insertRows);
        }
    }

    public function removeQuestion($testId, $questionId)
    {
        return DB::table('test_questions')->where('test_id', $testId)->where('question_id', $questionId)->delete();
    }

    public function listQuestions($testId)
    {
        return DB::table('test_questions as tq')
            ->join('questions as q', 'q.id', '=', 'tq.question_id')
            ->select('q.id', 'q.question_text', 'q.question_type', 'q.difficulty', 'tq.question_order')
            ->where('tq.test_id', $testId)
            ->orderBy('tq.question_order')
            ->get();
    }
}
