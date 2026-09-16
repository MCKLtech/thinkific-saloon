<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\PaginationPlugin\Paginator;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizLocation;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizSubmission;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizSubmissions;

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
     * Cost: nested connections multiply — each request costs roughly
     * $per_page x $answers_per_page x (choices per answer) against Thinkific's
     * per-request and per-minute point caps. The default of 10 is deliberately
     * conservative to stay under the per-request cap; size $answers_per_page to
     * the quiz's actual question count rather than a blanket large value, and
     * check hasMoreAnswers on each result to detect truncation.
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
}
