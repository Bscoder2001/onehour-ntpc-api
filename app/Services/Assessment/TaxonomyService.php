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
}
