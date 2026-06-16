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
     * Filter keys: userIds, courseIds, groupIds
     *
     * @param array $filter
     * @param int   $per_page
     * @param int   $answers_per_page
     * @return Paginator
     */
    public function submissions(array $filter = [], int $per_page = 10, int $answers_per_page = 10): Paginator
    {
        return (new QuizSubmissions($filter, $per_page, $answers_per_page))
            ->paginate($this->connector);
    }

    /**
     * Return quiz submissions for a specific user.
     *
     * @param int $userId
     * @param int $per_page
     * @param int $answers_per_page
     * @return Paginator
     */
    public function submissionsForUser(int $userId, int $per_page = 10, int $answers_per_page = 10): Paginator
    {
        return $this->submissions(['userIds' => [$userId]], $per_page, $answers_per_page);
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
