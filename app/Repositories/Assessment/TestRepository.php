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
        $ids = array_values(array_unique(array_map('intval', (array) $questionIds)));
        $ids = array_filter($ids, function ($qid)
        {
            return $qid > 0;
        });

        if (empty($ids))
        {
            return;
        }

        $existing = DB::table('test_questions')
            ->where('test_id', $testId)
            ->pluck('question_id')
            ->map(function ($q)
            {
                return (int) $q;
            })
            ->all();
        $existingSet = array_fill_keys($existing, true);
        $maxOrder = (int) DB::table('test_questions')->where('test_id', $testId)->max('question_order');
        $order = $maxOrder;
        $insertRows = [];

        foreach ($ids as $questionId)
        {
            if (isset($existingSet[$questionId]))
            {
                continue;
            }

            $order++;
            $existingSet[$questionId] = true;
            $insertRows[] = [
                'test_id' => $testId,
                'question_id' => $questionId,
                'question_order' => $order,
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
