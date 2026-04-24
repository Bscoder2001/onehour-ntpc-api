<?php

namespace App\Repositories\Assessment;

use Illuminate\Support\Facades\DB;

class AttemptRepository
{
    public function createAttempt($payload)
    {
        return (int) DB::table('attempts')->insertGetId($payload);
    }

    public function findAttemptById($attemptId)
    {
        return DB::table('attempts')->where('id', $attemptId)->first();
    }

    public function listTestQuestions($testId)
    {
        return DB::table('test_questions as tq')
            ->join('questions as q', 'q.id', '=', 'tq.question_id')
            ->select('q.id', 'q.topic_id', 'q.question_type', 'q.correct_numeric_answer')
            ->where('tq.test_id', $testId)
            ->orderBy('tq.question_order')
            ->get();
    }

    public function upsertAnswer($payload)
    {
        $existing = DB::table('attempt_answers')
            ->where('attempt_id', $payload['attempt_id'])
            ->where('question_id', $payload['question_id'])
            ->first();

        if ($existing)
        {
            DB::table('attempt_answers')->where('id', $existing->id)->update([
                'selected_option_id' => isset($payload['selected_option_id']) ? $payload['selected_option_id'] : null,
                'numeric_answer' => isset($payload['numeric_answer']) ? $payload['numeric_answer'] : null,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('attempt_answers')->insert([
            'attempt_id' => $payload['attempt_id'],
            'question_id' => $payload['question_id'],
            'selected_option_id' => isset($payload['selected_option_id']) ? $payload['selected_option_id'] : null,
            'numeric_answer' => isset($payload['numeric_answer']) ? $payload['numeric_answer'] : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function listAttemptAnswers($attemptId)
    {
        return DB::table('attempt_answers')->where('attempt_id', $attemptId)->get();
    }

    public function getCorrectOptionMap($questionIds)
    {
        return DB::table('question_options')
            ->select('question_id', 'id')
            ->whereIn('question_id', $questionIds)
            ->where('is_correct', 1)
            ->get()
            ->mapWithKeys(function ($item)
            {
                return [(int) $item->question_id => (int) $item->id];
            })
            ->toArray();
    }

    public function updateAttemptSubmission($attemptId, $payload)
    {
        DB::table('attempts')->where('id', $attemptId)->update($payload);
    }

    public function upsertTopicPerformance($rows)
    {
        foreach ($rows as $row)
        {
            $existing = DB::table('topic_performance')
                ->where('attempt_id', $row['attempt_id'])
                ->where('topic_id', $row['topic_id'])
                ->first();

            if ($existing)
            {
                DB::table('topic_performance')->where('id', $existing->id)->update($row);
            }
            else
            {
                DB::table('topic_performance')->insert($row);
            }
        }
    }

    public function listTopicPerformance($attemptId)
    {
        return DB::table('topic_performance')
            ->where('attempt_id', $attemptId)
            ->orderByDesc('accuracy')
            ->get();
    }

    public function listResultsByUser($userId)
    {
        return DB::table('attempts as a')
            ->join('tests as t', 't.id', '=', 'a.test_id')
            ->select('a.id as attempt_id', 'a.user_id', 'a.test_id', 't.title as test_title', 'a.score', 'a.accuracy', 'a.submitted_at')
            ->where('a.user_id', $userId)
            ->where('a.status', 'submitted')
            ->orderByDesc('a.id')
            ->get();
    }
}
