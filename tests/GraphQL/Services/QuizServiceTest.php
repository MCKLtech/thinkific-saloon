<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizLocation;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizSubmission;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Course as CourseRequest;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Courses;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizSubmissions;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class QuizServiceTest extends GraphQLTestCase
{
    /**
     * A QuizSubmission node exactly as QuizSubmissions::createDtoFromResponse()
     * expects it - distinct from GraphQLTestCase::gqlSubmissionNode(), which is
     * shaped for the (unrelated) Assignments query.
     */
    private function gqlQuizSubmissionNode(array $overrides = []): array
    {
        return array_merge([
            'id'             => 'sub_301',
            'attempts'       => 1,
            'correctCount'   => 3,
            'incorrectCount' => 1,
            'passed'         => true,
            'completedAt'    => '2024-01-15T10:00:00Z',
            'createdAt'      => '2024-01-14T09:00:00Z',
            'quiz'           => ['id' => 'quiz_1', 'name' => 'Chapter 1 Quiz', 'passingScore' => 70],
            'user'           => ['id' => 1, 'email' => 'bob@example.com', 'firstName' => 'Bob', 'lastName' => 'Smith'],
            'userAnswers'    => [
                'nodes' => [[
                    'question' => ['id' => 'q_1', 'prompt' => '<p>What is 2+2?</p>'],
                    'choices'  => [
                        ['id' => 'c_1', 'text' => 'Four', 'position' => 1, 'correct' => true],
                    ],
                ]],
            ],
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // submissions() / submissionsForUser()
    // -------------------------------------------------------------------------

    public function test_submissions_returns_quiz_submission_dtos(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->quizzes->submissions()->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(QuizSubmission::class, $items[0]);
        $this->assertEquals('sub_301', $items[0]->id);
        $this->assertTrue($items[0]->passed);
        $this->assertEquals(75.0, $items[0]->percentageScore());
    }

    public function test_submissions_maps_user_answers_and_strips_html_from_prompts(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $this->assertCount(1, $submission->userAnswers);
        $this->assertEquals('What is 2+2?', $submission->userAnswers[0]->question->prompt);
        $this->assertEquals('Four', $submission->userAnswers[0]->choices[0]->text);
        $this->assertTrue($submission->userAnswers[0]->choices[0]->correct);
    }

    public function test_submissions_for_user_filters_by_user_id(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->quizzes->submissionsForUser(1)->items());

        $this->assertCount(1, $items);
        $this->assertEquals(1, $items[0]->user->id);
    }

    public function test_submissions_terminates_at_last_page(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()], hasNextPage: false)],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->quizzes->submissions() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    // -------------------------------------------------------------------------
    // quizLocationsForCourse()
    // -------------------------------------------------------------------------

    public function test_quiz_locations_for_course_indexes_quiz_bearing_lessons_by_quiz_id(): void
    {
        $chapter = $this->gqlChapterNode();
        // gqlChapterNode's lesson content has no quiz - add one to exercise the mapping.
        $chapter['lessons']['nodes'][0]['content']['quiz'] = ['id' => 'quiz_1'];

        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => ['curriculum' => ['chapters' => $this->gqlConnection([$chapter])]],
            ]),
        ]);

        $index = $this->gql->quizzes->quizLocationsForCourse(101);

        $this->assertArrayHasKey('quiz_1', $index);
        $this->assertInstanceOf(QuizLocation::class, $index['quiz_1']);
        $this->assertEquals('quiz_1', $index['quiz_1']->quizId);
        $this->assertEquals(101, $index['quiz_1']->courseId);
        $this->assertEquals(10, $index['quiz_1']->chapterId);
        $this->assertEquals('Chapter 1', $index['quiz_1']->chapterTitle);
        $this->assertEquals(401, $index['quiz_1']->lessonId);
        $this->assertEquals('Lesson 1', $index['quiz_1']->lessonTitle);
        $this->assertEquals(501, $index['quiz_1']->contentId);
        // A bare course id was passed, not a Course DTO, so name/slug stay null.
        $this->assertNull($index['quiz_1']->courseName);
        $this->assertNull($index['quiz_1']->courseSlug);
    }

    public function test_quiz_locations_for_course_populates_course_name_and_slug_from_course_dto(): void
    {
        $chapter = $this->gqlChapterNode();
        $chapter['lessons']['nodes'][0]['content']['quiz'] = ['id' => 'quiz_1'];

        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => ['curriculum' => ['chapters' => $this->gqlConnection([$chapter])]],
            ]),
        ]);

        $course = new Course(id: 101, title: 'Intro to PHP', name: 'intro-to-php', slug: 'intro-to-php-slug');

        $index = $this->gql->quizzes->quizLocationsForCourse($course);

        // quizLocationsForCourse() populates courseName from Course::$name (not $title).
        $this->assertEquals('intro-to-php', $index['quiz_1']->courseName);
        $this->assertEquals('intro-to-php-slug', $index['quiz_1']->courseSlug);
    }

    public function test_quiz_locations_for_course_skips_lessons_without_a_quiz(): void
    {
        // gqlChapterNode's default lesson content has no 'quiz' key at all.
        $this->mockGql([
            CourseRequest::class => $this->gqlResponse([
                'course' => ['curriculum' => ['chapters' => $this->gqlConnection([$this->gqlChapterNode()])]],
            ]),
        ]);

        $index = $this->gql->quizzes->quizLocationsForCourse(101);

        $this->assertEmpty($index);
    }

    // -------------------------------------------------------------------------
    // quizLocations()
    // -------------------------------------------------------------------------

    public function test_quiz_locations_walks_every_course_and_merges_indexes(): void
    {
        $chapterForCourse101 = $this->gqlChapterNode();
        $chapterForCourse101['lessons']['nodes'][0]['content']['quiz'] = ['id' => 'quiz_1'];

        $this->mockGql([
            Courses::class => $this->gqlResponse([
                'site' => ['courses' => $this->gqlConnection([
                    $this->gqlCourseNode(['id' => '101']),
                ])],
            ]),
            CourseRequest::class => $this->gqlResponse([
                'course' => ['curriculum' => ['chapters' => $this->gqlConnection([$chapterForCourse101])]],
            ]),
        ]);

        $index = $this->gql->quizzes->quizLocations();

        $this->assertArrayHasKey('quiz_1', $index);
        $this->assertEquals(101, $index['quiz_1']->courseId);
    }

    // -------------------------------------------------------------------------
    // locateSubmission()
    // -------------------------------------------------------------------------

    public function test_locate_submission_resolves_from_index(): void
    {
        $location = new QuizLocation(
            quizId: 'quiz_1',
            courseId: 101,
            courseName: 'intro-to-php',
            courseSlug: 'intro-to-php-slug',
            chapterId: 10,
            chapterTitle: 'Chapter 1',
            lessonId: 401,
            lessonTitle: 'Lesson 1',
            contentId: 501,
        );

        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $resolved = $this->gql->quizzes->locateSubmission($submission, ['quiz_1' => $location]);

        $this->assertSame($location, $resolved);
    }

    public function test_locate_submission_returns_null_when_not_in_index(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([
                    $this->gqlQuizSubmissionNode(['quiz' => ['id' => 'quiz_unknown', 'name' => null, 'passingScore' => null]]),
                ])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $resolved = $this->gql->quizzes->locateSubmission($submission, []);

        $this->assertNull($resolved);
    }
}
