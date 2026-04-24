<?php

namespace App\Services\Assessment;

use App\Repositories\Assessment\AttemptRepository;
use Illuminate\Support\Facades\DB;

class AttemptService
{
    private $attemptRepository;

    public function __construct(AttemptRepository $attemptRepository)
    {
        $this->attemptRepository = $attemptRepository;
    }

    public function startAttempt($testId, $userId)
    {
        $attemptId = $this->attemptRepository->createAttempt([
            'test_id' => $testId,
            'user_id' => $userId,
            'status' => 'started',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $questions = $this->attemptRepository->listTestQuestions($testId);

        return [
            'attempt_id' => $attemptId,
            'test_id' => $testId,
            'questions_count' => $questions->count(),
            'questions' => $questions,
        ];
    }

    public function saveAnswer($attemptId, $data)
    {
        $attempt = $this->attemptRepository->findAttemptById($attemptId);

        if (!$attempt || $attempt->status === 'submitted')
        {
            throw new \RuntimeException('Attempt not active');
        }

        $this->attemptRepository->upsertAnswer([
            'attempt_id' => $attemptId,
            'question_id' => (int) $data['question_id'],
            'selected_option_id' => isset($data['selected_option_id']) ? (int) $data['selected_option_id'] : null,
            'numeric_answer' => isset($data['numeric_answer']) ? $data['numeric_answer'] : null,
        ]);

        return [
            'attempt_id' => $attemptId,
            'question_id' => (int) $data['question_id'],
        ];
    }

    public function submitAttempt($attemptId)
    {
        return DB::transaction(function () use ($attemptId)
        {
            $attempt = $this->attemptRepository->findAttemptById($attemptId);

            if (!$attempt)
            {
                throw new \RuntimeException('Attempt not found');
            }

            $testQuestions = $this->attemptRepository->listTestQuestions((int) $attempt->test_id);
            $answers = $this->attemptRepository->listAttemptAnswers($attemptId)->keyBy('question_id');
            $correctOptionMap = $this->attemptRepository->getCorrectOptionMap($testQuestions->pluck('id')->all());

            $total = $testQuestions->count();
            $correct = 0;
            $topicStats = [];

            foreach ($testQuestions as $question)
            {
                $answer = $answers->get($question->id);
                $isCorrect = false;

                if ($answer)
                {
                    if ($question->question_type === 'mcq')
                    {
                        $isCorrect = ((int) (isset($answer->selected_option_id) ? $answer->selected_option_id : 0) === (int) (isset($correctOptionMap[$question->id]) ? $correctOptionMap[$question->id] : 0));
                    }
                    else
                    {
                        $isCorrect = trim((string) $answer->numeric_answer) === trim((string) $question->correct_numeric_answer);
                    }
                }

                if ($isCorrect)
                {
                    $correct++;
                }

                $topicId = (int) $question->topic_id;
                if (!isset($topicStats[$topicId]))
                {
                    $topicStats[$topicId] = ['total' => 0, 'correct' => 0];
                }

                $topicStats[$topicId]['total']++;
                if ($isCorrect)
                {
                    $topicStats[$topicId]['correct']++;
                }
            }

            $score = $correct;
            $accuracy = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

            $this->attemptRepository->updateAttemptSubmission($attemptId, [
                'status' => 'submitted',
                'submitted_at' => now(),
                'score' => $score,
                'total_questions' => $total,
                'correct_answers' => $correct,
                'accuracy' => $accuracy,
                'updated_at' => now(),
            ]);

            $topicRows = [];
            foreach ($topicStats as $topicId => $stats)
            {
                $topicRows[] = [
                    'attempt_id' => $attemptId,
                    'user_id' => (int) $attempt->user_id,
                    'topic_id' => $topicId,
                    'total_questions' => $stats['total'],
                    'correct_answers' => $stats['correct'],
                    'accuracy' => $stats['total'] > 0 ? round(($stats['correct'] / $stats['total']) * 100, 2) : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->attemptRepository->upsertTopicPerformance($topicRows);

            return [
                'attempt_id' => $attemptId,
                'score' => $score,
                'accuracy' => $accuracy,
                'total_questions' => $total,
                'correct_answers' => $correct,
            ];
        });
    }

    public function resultByAttempt($attemptId)
    {
        $attempt = $this->attemptRepository->findAttemptById($attemptId);

        if (!$attempt)
        {
            throw new \RuntimeException('Attempt not found');
        }

        return [
            'attempt' => $attempt,
            'topic_performance' => $this->attemptRepository->listTopicPerformance($attemptId),
        ];
    }

    public function resultByUser($userId)
    {
        return $this->attemptRepository->listResultsByUser($userId);
    }
}
