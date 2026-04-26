<?php

namespace App\Services\Assessment;

use App\Repositories\Assessment\TaxonomyRepository;

class TaxonomyService
{
    private $taxonomyRepository;

    public function __construct(TaxonomyRepository $taxonomyRepository)
    {
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function listSubjects($courseId)
    {
        return $this->taxonomyRepository->listSubjectsByCourse($courseId);
    }

    public function listChapters($subjectId)
    {
        return $this->taxonomyRepository->listChaptersBySubject($subjectId);
    }

    public function listTopics($chapterId)
    {
        return $this->taxonomyRepository->listTopicsByChapter($chapterId);
    }

    public function validateHierarchy($courseId, $subjectId, $chapterId, $topicId)
    {
        if (!$this->taxonomyRepository->isValidSubjectForCourse($subjectId, $courseId))
        {
            return false;
        }

        if (!$this->taxonomyRepository->isValidChapterForSubject($chapterId, $subjectId))
        {
            return false;
        }

        if (!$this->taxonomyRepository->isValidTopicForChapter($topicId, $chapterId))
        {
            return false;
        }

        return true;
    }

    public function createSubject($courseId, $name)
    {
        if (!$this->taxonomyRepository->findCourse($courseId))
        {
            throw new \RuntimeException('Course not found');
        }

        $id = $this->taxonomyRepository->createSubject($courseId, $name);
        $label = trim($name);

        return [
            'id' => $id,
            'name' => $label,
            'course_id' => (int) $courseId,
        ];
    }

    public function createChapter($subjectId, $name)
    {
        if (!$this->taxonomyRepository->findSubject($subjectId))
        {
            throw new \RuntimeException('Subject not found');
        }

        $id = $this->taxonomyRepository->createChapter($subjectId, $name);
        $label = trim($name);

        return [
            'id' => $id,
            'name' => $label,
            'subject_id' => (int) $subjectId,
        ];
    }

    public function createTopic($chapterId, $name)
    {
        if (!$this->taxonomyRepository->findChapter($chapterId))
        {
            throw new \RuntimeException('Chapter not found');
        }

        $id = $this->taxonomyRepository->createTopic($chapterId, $name);
        $label = trim($name);

        return [
            'id' => $id,
            'name' => $label,
            'chapter_id' => (int) $chapterId,
        ];
    }
}
