<?php

namespace App\Repositories\Assessment;

use Illuminate\Support\Facades\DB;

class TaxonomyRepository
{
    public function createSubject($courseId, $name)
    {
        $row = [
            'course_id' => (int) $courseId,
            'name' => trim($name),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('subjects')->insertGetId($row);
    }

    public function createChapter($subjectId, $name)
    {
        $row = [
            'subject_id' => (int) $subjectId,
            'name' => trim($name),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('chapters')->insertGetId($row);
    }

    public function createTopic($chapterId, $name)
    {
        $row = [
            'chapter_id' => (int) $chapterId,
            'name' => trim($name),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('topics')->insertGetId($row);
    }

    public function findCourse($courseId)
    {
        return DB::table('courses')->where('id', (int) $courseId)->first();
    }

    public function findSubject($subjectId)
    {
        return DB::table('subjects')->where('id', (int) $subjectId)->first();
    }

    public function findChapter($chapterId)
    {
        return DB::table('chapters')->where('id', (int) $chapterId)->first();
    }

    public function listSubjectsByCourse($courseId)
    {
        return DB::table('subjects')
            ->select('id', 'name', 'course_id')
            ->where('course_id', $courseId)
            ->orderBy('name')
            ->get();
    }

    public function listChaptersBySubject($subjectId)
    {
        return DB::table('chapters')
            ->select('id', 'name', 'subject_id')
            ->where('subject_id', $subjectId)
            ->orderBy('name')
            ->get();
    }

    public function listTopicsByChapter($chapterId)
    {
        return DB::table('topics')
            ->select('id', 'name', 'chapter_id')
            ->where('chapter_id', $chapterId)
            ->orderBy('name')
            ->get();
    }

    public function isValidSubjectForCourse($subjectId, $courseId)
    {
        return DB::table('subjects')
            ->where('id', $subjectId)
            ->where('course_id', $courseId)
            ->exists();
    }

    public function isValidChapterForSubject($chapterId, $subjectId)
    {
        return DB::table('chapters')
            ->where('id', $chapterId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    public function isValidTopicForChapter($topicId, $chapterId)
    {
        return DB::table('topics')
            ->where('id', $topicId)
            ->where('chapter_id', $chapterId)
            ->exists();
    }
}
