<?php

namespace App\Repositories\Assessment;

use Illuminate\Support\Facades\DB;

class QuestionRepository
{
    public function create($payload)
    {
        return (int) DB::table('questions')->insertGetId($payload);
    }

    public function getList($filters)
    {
        $query = DB::table('questions as q')
            ->select('q.id', 'q.question_text', 'q.question_type', 'q.difficulty', 'q.subject_id', 'q.chapter_id', 'q.topic_id', 'q.created_at')
            ->orderByDesc('q.id');

        if (!empty($filters['subject_id']))
        {
            $query->where('q.subject_id', (int) $filters['subject_id']);
        }

        if (!empty($filters['chapter_id']))
        {
            $query->where('q.chapter_id', (int) $filters['chapter_id']);
        }

        if (!empty($filters['topic_id']))
        {
            $query->where('q.topic_id', (int) $filters['topic_id']);
        }

        if (!empty($filters['difficulty']))
        {
            $query->where('q.difficulty', $filters['difficulty']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;

        return $query->paginate($perPage);
    }

    public function findById($id)
    {
        return DB::table('questions')->where('id', $id)->first();
    }

    public function updateById($id, $payload)
    {
        return DB::table('questions')->where('id', $id)->update($payload);
    }

    public function deleteById($id)
    {
        return DB::table('questions')->where('id', $id)->delete();
    }

    public function upsertOptions($questionId, $options)
    {
        DB::table('question_options')->where('question_id', $questionId)->delete();

        if (empty($options))
        {
            return;
        }

        $rows = [];
        foreach ($options as $index => $option)
        {
            $rows[] = [
                'question_id' => $questionId,
                'option_text' => $option['option_text'],
                'is_correct' => !empty($option['is_correct']) ? 1 : 0,
                'option_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('question_options')->insert($rows);
    }

    public function getOptionsByQuestionId($questionId)
    {
        return DB::table('question_options')
            ->where('question_id', $questionId)
            ->orderBy('option_order')
            ->get();
    }
}
