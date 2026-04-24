<?php

namespace App\Repositories\Assessment;

use Illuminate\Support\Facades\DB;

class TaxonomyRepository
{
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
