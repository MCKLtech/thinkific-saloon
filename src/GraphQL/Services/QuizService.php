<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinition;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizLocation;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizSubmission;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizDefinitions;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizSubmissions;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizSubmissionsSummary;

class QuizService extends Resource
{
    /**
     * Return a paginated list of quiz submissions.
     *
     * Filter keys: userIds, courseIds, groupIds, questionGroupIds, completedAt (DateTimeFilter)
     *
     * Each submission carries at most $answers_per_page answers. When a submission
     * has more, its DTO reports hasMoreAnswers = true and answersEndCursor. The
     * schema has no by-id lookup for a QuizSubmission, so to drain the rest re-issue
     * this query with a filter narrow enough to return that submission (e.g. its
     * userIds + courseIds) and pass answersEndCursor as $answers_after:
     *
     *   $answers = $submission->userAnswers;
     *   $cursor  = $submission->hasMoreAnswers ? $submission->answersEndCursor : null;
     *   while ($cursor !== null) {
     *       foreach ($client->quizzes->submissions($filter, answers_after: $cursor)->items() as $page) {
     *           if ($page->id !== $submission->id) {
     *               continue;
     *           }
     *           $answers = [...$answers, ...$page->userAnswers];
     *           $cursor  = $page->hasMoreAnswers ? $page->answersEndCursor : null;
     *           break;
     *       }
     *   }
     *
     * Note $answers_after is applied to every submission in the page, so only
     * the answers of the submission the cursor came from are meaningful.
     *
     * Cost: Thinkific rejects any single request costing more than 1000 points
     * (MAX_QUERY_COST_EXCEEDED, returned as HTTP 200 and thrown here as
     * MaxQueryCostExceededException). Nested connections multiply: this query's
     * cost scales with $per_page x $answers_per_page, so 10 x 50 is rejected in
     * production while the 10 x 10 defaults are not. Size $answers_per_page to
     * the quiz's actual question count rather than a blanket large value, and
     * check hasMoreAnswers on each result to detect truncation. A rejected
     * query is not charged against the per-minute budget, so retrying with the
     * same sizes fails identically — shrink the page instead.
     *
     * @param array       $filter
     * @param int         $per_page
     * @param int         $answers_per_page
     * @param string|null $answers_after  userAnswers cursor from a prior QuizSubmission::$answersEndCursor
     * @return Paginator
     */
    public function submissions(array $filter = [], int $per_page = 10, int $answers_per_page = 10, ?string $answers_after = null): Paginator
    {
        return (new QuizSubmissions($filter, $per_page, $answers_per_page, $answers_after))
            ->paginate($this->connector);
    }

    /**
     * Return quiz submissions for a specific user.
     *
     * @param int         $userId
     * @param int         $per_page
     * @param int         $answers_per_page
     * @param string|null $answers_after  See submissions()
     * @return Paginator
     */
    public function submissionsForUser(int $userId, int $per_page = 10, int $answers_per_page = 10, ?string $answers_after = null): Paginator
    {
        return $this->submissions(['userIds' => [$userId]], $per_page, $answers_per_page, $answers_after);
    }

    /**
     * Return quiz submissions for several users through one paginated operation.
     *
     * @param int[]       $userIds
     * @param int         $per_page
     * @param int         $answers_per_page
     * @param string|null $answers_after  See submissions()
     * @return Paginator
     */
    public function submissionsForUsers(array $userIds, int $per_page = 10, int $answers_per_page = 10, ?string $answers_after = null): Paginator
    {
        return $this->submissions(['userIds' => array_values($userIds)], $per_page, $answers_per_page, $answers_after);
    }

    /**
     * Return a paginated list of quiz submissions WITHOUT the per-question answers.
     *
     * This is the cheap shape of the same connection: it selects only the
     * submission scalars, quiz and user. Measured cost is ~22 points for 10 rows
     * versus ~522 for the same page with the userAnswers block (~25x cheaper), so
     * use it whenever only counts/attempts are needed.
     *
     * Because answers are never requested, every returned
     * QuizSubmission::$userAnswers is [] and $hasMoreAnswers is false. Use the
     * full submissions()/submissionsForUser()/submissionsForUsers() when
     * per-question answers are required.
     *
     * @param array $filter    Same filter keys as submissions()
     * @param int   $per_page
     * @return Paginator
     */
    public function submissionsSummary(array $filter = [], int $per_page = 10): Paginator
    {
        return (new QuizSubmissionsSummary($filter, $per_page))
            ->paginate($this->connector);
    }

    /**
     * Return the no-answers quiz submissions for a specific user.
     *
     * @param int $userId
     * @param int $per_page
     * @return Paginator
     */
    public function submissionsSummaryForUser(int $userId, int $per_page = 10): Paginator
    {
        return $this->submissionsSummary(['userIds' => [$userId]], $per_page);
    }

    /**
     * Return the no-answers quiz submissions for several users.
     *
     * @param int[] $userIds
     * @param int   $per_page
     * @return Paginator
     */
    public function submissionsSummaryForUsers(array $userIds, int $per_page = 10): Paginator
    {
        return $this->submissionsSummary(['userIds' => array_values($userIds)], $per_page);
    }

    /**
     * Build a map of Quiz id => QuizLocation (course + chapter + lesson) for a SINGLE course.
     *
     * This is the unit of work to fan out across queued jobs. The GraphQL API is
     * cursor-paginated (you cannot address an arbitrary "page N" in parallel), so the
     * course — not a page number — is the independently dispatchable chunk. A coordinator
     * lists courses once and dispatches one job per course, e.g.:
     *
     *   foreach ($client->courses->courses()->items() as $course) {
     *       $jobs[] = new BuildQuizLocations($course); // Course DTO is serializable
     *   }
     *   Bus::batch($jobs)->dispatch();
     *
     *   // inside the job:
     *   $locations = $client->quizzes->quizLocationsForCourse($course);
     *
     * Pass a Course DTO to populate courseName/courseSlug; passing a bare id leaves them null.
     *
     * @return array<string, QuizLocation> Keyed by Quiz id
     */
    public function quizLocationsForCourse(Course|int $course, int $chapters_per_page = 5, int $lessons_per_page = 5): array
    {
        $courseId   = $course instanceof Course ? $course->id : $course;
        $courseName = $course instanceof Course ? $course->name : null;
        $courseSlug = $course instanceof Course ? $course->slug : null;

        $index = [];

        foreach ($this->service->courses->chapters($courseId, $chapters_per_page, $lessons_per_page)->items() as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                $quizId = $lesson->content?->quizId;

                if ($quizId === null) {
                    continue;
                }

                $index[$quizId] = new QuizLocation(
                    quizId: $quizId,
                    courseId: $courseId,
                    courseName: $courseName,
                    courseSlug: $courseSlug,
                    chapterId: $chapter->id,
                    chapterTitle: $chapter->title,
                    lessonId: $lesson->id,
                    lessonTitle: $lesson->title,
                    contentId: $lesson->content->id,
                );
            }
        }

        return $index;
    }

    /**
     * Build the Quiz id => QuizLocation map for every Course on the site by walking
     * the whole curriculum in-process.
     *
     * The GraphQL schema only exposes the Content -> Quiz relationship, not the reverse,
     * so a QuizSubmission cannot tell you which Lesson/Course its Quiz belongs to directly.
     *
     * Note: this is expensive — one query to list courses plus one (paginated) query per
     * course, all sequentially. For large sites prefer fanning out with
     * quizLocationsForCourse() (one queued job per course) and merging the results.
     *
     * @return array<string, QuizLocation> Keyed by Quiz id
     */
    public function quizLocations(int $courses_per_page = 5, int $chapters_per_page = 5, int $lessons_per_page = 5): array
    {
        $index = [];

        foreach ($this->service->courses->courses($courses_per_page)->items() as $course) {
            $index = array_replace($index, $this->quizLocationsForCourse($course, $chapters_per_page, $lessons_per_page));
        }

        return $index;
    }

    /**
     * Resolve the Course/Chapter/Lesson a submission's Quiz belongs to, using an index
     * previously built by quizLocations() or merged from quizLocationsForCourse().
     *
     * @param array<string, QuizLocation> $index
     */
    public function locateSubmission(QuizSubmission $submission, array $index): ?QuizLocation
    {
        return $index[$submission->quiz->id] ?? null;
    }

    /**
     * Return a cursor-paginated list of quiz definitions (Site.quizzes -> questions -> choices).
     * Yields QuizDefinition[] per page. Use allDefinitions() for the assembled map.
     * Cost: charged 2 + ceil(per_page * questions_per_page * choices_per_page / 100) per request
     * (25x25x15 measured 96 points; a single request stays far under the 1000-point cap). The
     * binding budget is the 2000-points/minute rate limit. choices are NOT drained: a question
     * with hasMoreChoices=true is surfaced on the DTO for the caller to treat as incomplete.
     *
     * Note: the paginator throws PaginationException on a stalled outer cursor (5 identical pages);
     * allDefinitions() does its own raw walk and instead returns early on a non-advancing cursor.
     */
    public function definitions(array $filter = [], int $per_page = 25, int $questions_per_page = 25, int $choices_per_page = 15): Paginator
    {
        return (new QuizDefinitions($filter, $per_page, $questions_per_page, $choices_per_page))
            ->paginate($this->connector);
    }

    /**
     * Walk every quiz definition and return them keyed by quiz id, draining each truncated
     * quiz's questions connection. No page cap: the walk terminates on hasNextPage=false or a
     * non-advancing cursor.
     *
     * @return array<string, QuizDefinition>
     */
    public function allDefinitions(array $filter = [], int $per_page = 25, int $questions_per_page = 25, int $choices_per_page = 15): array
    {
        $index = [];
        $after = null;

        while (true) {
            $response  = $this->connector->send($this->newQuizDefinitionsRequest($filter, $per_page, $questions_per_page, $choices_per_page, $after));
            $pageInfo  = $response->json('data.site.quizzes.pageInfo') ?? [];
            $hasNext   = (bool) ($pageInfo['hasNextPage'] ?? false);
            $endCursor = $pageInfo['endCursor'] ?? null;

            foreach ($response->dto() as $quiz) {
                $index[$quiz->id] = $this->drainQuizQuestions($quiz, $after, $filter, $per_page, $questions_per_page, $choices_per_page);
            }

            if (! $hasNext || $endCursor === null || $endCursor === '' || $endCursor === $after) {
                break;
            }

            $after = $endCursor;
        }

        return $index;
    }

    /**
     * Drain a single quiz's remaining questions by re-issuing the SAME outer page with the
     * questions cursor and re-selecting the quiz by id (questionsAfter applies to every quiz
     * on the page, so siblings must be ignored).
     */
    private function drainQuizQuestions(QuizDefinition $quiz, ?string $after, array $filter, int $per_page, int $questions_per_page, int $choices_per_page): QuizDefinition
    {
        $cursor = $quiz->hasMoreQuestions ? $quiz->questionsEndCursor : null;

        while ($cursor !== null && $cursor !== '') {
            $response = $this->connector->send(
                $this->newQuizDefinitionsRequest($filter, $per_page, $questions_per_page, $choices_per_page, $after, $cursor)
            );

            $next = null;
            foreach ($response->dto() as $candidate) {
                if ($candidate->id === $quiz->id) {
                    $next = $candidate;
                    break;
                }
            }

            if ($next === null) {
                break;
            }

            $quiz->questions          = array_merge($quiz->questions, $next->questions);
            $quiz->hasMoreQuestions   = $next->hasMoreQuestions;
            $quiz->questionsEndCursor = $next->questionsEndCursor;

            $nextCursor = $next->hasMoreQuestions ? $next->questionsEndCursor : null;

            if ($nextCursor === null || $nextCursor === '' || $nextCursor === $cursor) {
                break;
            }

            $cursor = $nextCursor;
        }

        return $quiz;
    }

    private function newQuizDefinitionsRequest(array $filter, int $per_page, int $questions_per_page, int $choices_per_page, ?string $after, ?string $questions_after = null): QuizDefinitions
    {
        $request           = new QuizDefinitions($filter, $per_page, $questions_per_page, $choices_per_page, $questions_after);
        $request->after    = $after;

        return $request;
    }
}
