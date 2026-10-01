<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use Carbon\Carbon;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Courses\Course;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\Quiz;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinition;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinitionChoice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizDefinitionQuestion;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizLocation;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Quizzes\QuizSubmission;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Course as CourseRequest;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Courses\Courses;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Quizzes\QuizDefinitions;
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

    public function test_submissions_hydrates_question_position_and_type(): void
    {
        $node = $this->gqlQuizSubmissionNode();
        $node['userAnswers']['nodes'][0]['question'] = [
            'id'       => 'q_1',
            'prompt'   => '<p>Pick all that apply</p>',
            'position' => 3,
            'type'     => 'MULTIPLE_ANSWERS',
        ];

        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$node])],
            ]),
        ]);

        $question = iterator_to_array($this->gql->quizzes->submissions()->items())[0]->userAnswers[0]->question;

        $this->assertSame(3, $question->position);
        $this->assertSame('MULTIPLE_ANSWERS', $question->type);

        MockClient::getGlobal()->assertSent(function (QuizSubmissions $request) {
            $query = $request->body()->all()['query'];

            return str_contains($query, 'position') && str_contains($query, 'type');
        });
    }

    public function test_submissions_tolerates_unknown_question_type_and_missing_position(): void
    {
        // A future QuizQuestionType value must hydrate as a plain string, not throw.
        $node = $this->gqlQuizSubmissionNode();
        $node['userAnswers']['nodes'][0]['question'] = ['id' => 'q_1', 'prompt' => 'x', 'type' => 'SOME_NEW_TYPE'];

        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$node])],
            ]),
        ]);

        $question = iterator_to_array($this->gql->quizzes->submissions()->items())[0]->userAnswers[0]->question;

        $this->assertSame('SOME_NEW_TYPE', $question->type);
        $this->assertNull($question->position);
    }

    public function test_submissions_for_users_filters_by_all_user_ids(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([
                    $this->gqlQuizSubmissionNode(['id' => 'sub_1']),
                    $this->gqlQuizSubmissionNode(['id' => 'sub_2', 'user' => ['id' => 2, 'email' => 'amy@example.com']]),
                ])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->quizzes->submissionsForUsers(['a' => 1, 'b' => 2])->items());

        $this->assertCount(2, $items);

        MockClient::getGlobal()->assertSent(
            fn (QuizSubmissions $request) => $request->body()->all()['variables']['filter'] === ['userIds' => [1, 2]]
        );
    }

    public function test_service_answers_per_page_default_matches_request_default(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->quizzes->submissions()->items());
        iterator_to_array($this->gql->quizzes->submissionsForUser(1)->items());
        iterator_to_array($this->gql->quizzes->submissionsForUsers([1])->items());

        $request = new \ReflectionClass(QuizSubmissions::class);
        $requestDefault = $request->getConstructor()->getParameters()[2]->getDefaultValue();

        MockClient::getGlobal()->assertSentCount(3);
        foreach (MockClient::getGlobal()->getRecordedResponses() as $response) {
            $this->assertSame($requestDefault, $response->getPendingRequest()->body()->all()['variables']['answersFirst']);
        }
    }

    // -------------------------------------------------------------------------
    // userAnswers cursor paging
    // -------------------------------------------------------------------------

    public function test_submissions_exposes_answer_page_info_when_more_answers_exist(): void
    {
        $node = $this->gqlQuizSubmissionNode();
        $node['userAnswers']['pageInfo'] = ['hasNextPage' => true, 'endCursor' => 'ans_cursor_2'];

        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$node])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $this->assertTrue($submission->hasMoreAnswers);
        $this->assertEquals('ans_cursor_2', $submission->answersEndCursor);
    }

    public function test_submissions_reports_no_more_answers_on_last_answer_page(): void
    {
        $node = $this->gqlQuizSubmissionNode();
        $node['userAnswers']['pageInfo'] = ['hasNextPage' => false, 'endCursor' => 'ans_cursor_end'];

        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$node])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $this->assertFalse($submission->hasMoreAnswers);
        $this->assertEquals('ans_cursor_end', $submission->answersEndCursor);
    }

    public function test_submissions_defaults_answer_page_info_when_absent(): void
    {
        // gqlQuizSubmissionNode() has no userAnswers.pageInfo block.
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $this->assertFalse($submission->hasMoreAnswers);
        $this->assertNull($submission->answersEndCursor);
    }

    public function test_submissions_passes_answers_after_cursor_to_query(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->quizzes->submissions(
            filter: ['userIds' => [1]],
            answers_per_page: 5,
            answers_after: 'ans_cursor_2',
        )->items());

        MockClient::getGlobal()->assertSent(function (QuizSubmissions $request) {
            $body = $request->body()->all();

            return $body['variables']['answersFirst'] === 5
                && $body['variables']['answersAfter'] === 'ans_cursor_2'
                && str_contains($body['query'], 'userAnswers(first: $answersFirst, after: $answersAfter)')
                && str_contains($body['query'], 'hasNextPage');
        });
    }

    public function test_submissions_sends_null_answers_after_by_default(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->quizzes->submissions()->items());

        MockClient::getGlobal()->assertSent(
            fn (QuizSubmissions $request) => $request->body()->all()['variables']['answersAfter'] === null
        );
    }

    public function test_submissions_handles_null_user_answers_connection(): void
    {
        // QuizSubmission.userAnswers is nullable in the schema.
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([
                    $this->gqlQuizSubmissionNode(['userAnswers' => null]),
                ])],
            ]),
        ]);

        $submission = iterator_to_array($this->gql->quizzes->submissions()->items())[0];

        $this->assertSame([], $submission->userAnswers);
        $this->assertFalse($submission->hasMoreAnswers);
        $this->assertNull($submission->answersEndCursor);
    }

    public function test_request_answers_after_is_a_settable_public_property(): void
    {
        // Mirrors the outer $after cursor: the ctor arg seeds it, and callers building
        // the request directly can overwrite it before the body is resolved (Saloon
        // caches defaultBody() on first body() call, same as for $after).
        $seeded = new QuizSubmissions([], 10, 25, 'from_ctor');
        $this->assertSame('from_ctor', $seeded->answersAfter);
        $this->assertSame('from_ctor', $seeded->body()->all()['variables']['answersAfter']);

        $overridden = new QuizSubmissions([], 10, 25, 'from_ctor');
        $overridden->answersAfter = 'set_later';
        $this->assertSame('set_later', $overridden->body()->all()['variables']['answersAfter']);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility — pre-existing call shapes must keep working
    // -------------------------------------------------------------------------

    public function test_bc_quiz_submission_dto_constructs_without_new_arguments(): void
    {
        $dto = new QuizSubmission(
            id: 'sub_1',
            attempts: 1,
            correctCount: 1,
            incorrectCount: 0,
            passed: true,
            completedAt: null,
            createdAt: Carbon::parse('2024-01-01'),
            quiz: new Quiz(id: 'quiz_1'),
            user: new User(id: 1, email: 'bob@example.com'),
            userAnswers: [],
        );

        $this->assertFalse($dto->hasMoreAnswers);
        $this->assertNull($dto->answersEndCursor);
    }

    public function test_bc_quiz_submissions_request_constructs_with_original_positional_arguments(): void
    {
        $request = new QuizSubmissions(['userIds' => [1]], 5, 7);
        $vars    = $request->body()->all()['variables'];

        $this->assertSame(5, $vars['first']);
        $this->assertSame(7, $vars['answersFirst']);
        $this->assertNull($vars['answersAfter']);
    }

    public function test_bc_service_methods_accept_original_positional_arguments(): void
    {
        $this->mockGql([
            QuizSubmissions::class => $this->gqlResponse([
                'site' => ['quizSubmissions' => $this->gqlConnection([$this->gqlQuizSubmissionNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->quizzes->submissions(['userIds' => [1]], 5, 7)->items());
        iterator_to_array($this->gql->quizzes->submissionsForUser(1, 5, 7)->items());

        MockClient::getGlobal()->assertSentCount(2);
        foreach (MockClient::getGlobal()->getRecordedResponses() as $response) {
            $vars = $response->getPendingRequest()->body()->all()['variables'];
            $this->assertSame(5, $vars['first']);
            $this->assertSame(7, $vars['answersFirst']);
        }
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

    // -------------------------------------------------------------------------
    // definitions()
    // -------------------------------------------------------------------------

    public function test_definitions_returns_quiz_definition_dtos(): void
    {
        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$this->gqlQuizDefinitionNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->quizzes->definitions()->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(QuizDefinition::class, $items[0]);
        $this->assertEquals('quiz_1', $items[0]->id);
        $this->assertCount(1, $items[0]->questions);
        $this->assertInstanceOf(QuizDefinitionQuestion::class, $items[0]->questions[0]);
        $this->assertInstanceOf(QuizDefinitionChoice::class, $items[0]->questions[0]->choices[0]);
    }

    public function test_definitions_maps_questions_choices_and_correct_flag(): void
    {
        $node = $this->gqlQuizDefinitionNode([
            'name'      => '  <b>Chapter</b> 1 Quiz ',
            'questions' => [
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                'nodes'    => [[
                    'id'       => 'q_1',
                    'prompt'   => '<p>What is <em>2+2</em>?</p>',
                    'position' => 3,
                    'type'     => 'MULTIPLE_CHOICE',
                    'choices'  => [
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        'nodes'    => [
                            ['id' => 'c_1', 'text' => '<b>Four</b>', 'position' => 1, 'correct' => true],
                            ['id' => 'c_2', 'text' => 'Five', 'position' => 2, 'correct' => false],
                        ],
                    ],
                ]],
            ],
        ]);

        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$node])],
            ]),
        ]);

        $quiz = iterator_to_array($this->gql->quizzes->definitions()->items())[0];

        $this->assertEquals('Chapter 1 Quiz', $quiz->name);
        $this->assertEquals('What is 2+2?', $quiz->questions[0]->prompt);
        $this->assertEquals('Four', $quiz->questions[0]->choices[0]->text);
        $this->assertTrue($quiz->questions[0]->choices[0]->correct);
        $this->assertFalse($quiz->questions[0]->choices[1]->correct);
        $this->assertSame(3, $quiz->questions[0]->position);
    }

    public function test_definitions_terminates_at_last_page(): void
    {
        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$this->gqlQuizDefinitionNode()], hasNextPage: false)],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->quizzes->definitions() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_definitions_exposes_question_page_info(): void
    {
        $node = $this->gqlQuizDefinitionNode([
            'questions' => [
                'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'qs_cursor_2'],
                'nodes'    => [[
                    'id'       => 'q_1',
                    'prompt'   => 'What is 2+2?',
                    'position' => 1,
                    'type'     => 'MULTIPLE_CHOICE',
                    'choices'  => [
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        'nodes'    => [['id' => 'c_1', 'text' => 'Four', 'position' => 1, 'correct' => true]],
                    ],
                ]],
            ],
        ]);

        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$node])],
            ]),
        ]);

        $quiz = iterator_to_array($this->gql->quizzes->definitions()->items())[0];

        $this->assertTrue($quiz->hasMoreQuestions);
        $this->assertEquals('qs_cursor_2', $quiz->questionsEndCursor);
    }

    public function test_definitions_flags_truncated_choice_page(): void
    {
        $node = $this->gqlQuizDefinitionNode([
            'questions' => [
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                'nodes'    => [[
                    'id'       => 'q_1',
                    'prompt'   => 'Pick all',
                    'position' => 1,
                    'type'     => 'MULTIPLE_ANSWERS',
                    'choices'  => [
                        'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cs_cursor_2'],
                        'nodes'    => [['id' => 'c_1', 'text' => 'A', 'position' => 1, 'correct' => true]],
                    ],
                ]],
            ],
        ]);

        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$node])],
            ]),
        ]);

        $question = iterator_to_array($this->gql->quizzes->definitions()->items())[0]->questions[0];

        $this->assertTrue($question->hasMoreChoices);
        $this->assertEquals('cs_cursor_2', $question->choicesEndCursor);
    }

    public function test_definitions_passes_questions_after_cursor_to_query(): void
    {
        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$this->gqlQuizDefinitionNode()])],
            ]),
        ]);

        $request = new QuizDefinitions([], 25, 25, 15, 'qs_cursor_2');
        $this->gql->connector()->send($request);

        MockClient::getGlobal()->assertSent(function (QuizDefinitions $request) {
            $body = $request->body()->all();

            return $body['variables']['questionsAfter'] === 'qs_cursor_2'
                && str_contains($body['query'], 'questions(first: $questionsFirst, after: $questionsAfter)')
                && str_contains($body['query'], 'choices(first: $choicesFirst)')
                && str_contains($body['query'], 'correct');
        });
    }

    public function test_definitions_sends_null_questions_after_by_default(): void
    {
        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([$this->gqlQuizDefinitionNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->quizzes->definitions()->items());

        MockClient::getGlobal()->assertSent(
            fn (QuizDefinitions $request) => $request->body()->all()['variables']['questionsAfter'] === null
        );
    }

    public function test_definitions_request_constructs_with_original_positional_arguments(): void
    {
        $request = new QuizDefinitions([], 5, 6, 7);
        $vars    = $request->body()->all()['variables'];

        $this->assertSame(5, $vars['first']);
        $this->assertSame(6, $vars['questionsFirst']);
        $this->assertSame(7, $vars['choicesFirst']);
        $this->assertNull($vars['questionsAfter']);
    }

    // -------------------------------------------------------------------------
    // allDefinitions()
    // -------------------------------------------------------------------------

    public function test_all_definitions_returns_map_keyed_by_quiz_id(): void
    {
        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([
                    $this->gqlQuizDefinitionNode(['id' => 'quiz_a']),
                    $this->gqlQuizDefinitionNode(['id' => 'quiz_b']),
                ])],
            ]),
        ]);

        $index = $this->gql->quizzes->allDefinitions();

        $this->assertCount(2, $index);
        $this->assertArrayHasKey('quiz_a', $index);
        $this->assertArrayHasKey('quiz_b', $index);
        $this->assertInstanceOf(QuizDefinition::class, $index['quiz_a']);
    }

    public function test_all_definitions_drains_truncated_questions_by_id(): void
    {
        // Page 1 (after = null): quiz A complete. Page 2 (after = outer_2): quiz B truncated.
        // The drain of B must re-issue page 2 (same outer cursor, questionsAfter = b_cursor).
        $mock = function (PendingRequest $pending) {
            $vars = $pending->body()->all()['variables'];

            if ($vars['after'] === null) {
                return $this->gqlResponse([
                    'site' => ['quizzes' => [
                        'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'outer_2', 'hasPreviousPage' => false, 'startCursor' => 's'],
                        'nodes'    => [$this->gqlQuizDefinitionNode([
                            'id'        => 'quiz_a',
                            'questions' => [
                                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                                'nodes'    => [[
                                    'id' => 'a1', 'prompt' => 'A1', 'position' => 1, 'type' => 'MULTIPLE_CHOICE',
                                    'choices' => ['pageInfo' => ['hasNextPage' => false, 'endCursor' => null], 'nodes' => []],
                                ]],
                            ],
                        ])],
                    ]],
                ]);
            }

            // Page 2, first read has no questionsAfter; the drain read has questionsAfter=b_cursor.
            $questions = $vars['questionsAfter'] === null
                ? [[
                    'id' => 'b1', 'prompt' => 'B1', 'position' => 1, 'type' => 'MULTIPLE_CHOICE',
                    'choices' => ['pageInfo' => ['hasNextPage' => false, 'endCursor' => null], 'nodes' => []],
                ]]
                : [[
                    'id' => 'b2', 'prompt' => 'B2', 'position' => 2, 'type' => 'MULTIPLE_CHOICE',
                    'choices' => ['pageInfo' => ['hasNextPage' => false, 'endCursor' => null], 'nodes' => []],
                ]];

            return $this->gqlResponse([
                'site' => ['quizzes' => [
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'outer_end', 'hasPreviousPage' => false, 'startCursor' => 's'],
                    'nodes'    => [$this->gqlQuizDefinitionNode([
                        'id'        => 'quiz_b',
                        'questions' => [
                            'pageInfo' => $vars['questionsAfter'] === null
                                ? ['hasNextPage' => true, 'endCursor' => 'b_cursor']
                                : ['hasNextPage' => false, 'endCursor' => null],
                            'nodes'    => $questions,
                        ],
                    ])],
                ]],
            ]);
        };

        $this->mockGql([QuizDefinitions::class => $mock]);

        $index = $this->gql->quizzes->allDefinitions(per_page: 2);

        $this->assertCount(2, $index);
        $this->assertEquals(['a1'], array_map(fn ($q) => $q->id, $index['quiz_a']->questions));
        $this->assertEquals(['b1', 'b2'], array_map(fn ($q) => $q->id, $index['quiz_b']->questions));
        $this->assertFalse($index['quiz_b']->hasMoreQuestions);

        MockClient::getGlobal()->assertSent(function (QuizDefinitions $request) {
            $vars = $request->body()->all()['variables'];

            return $vars['after'] === 'outer_2' && $vars['questionsAfter'] === 'b_cursor';
        });
    }

    public function test_all_definitions_completes_beyond_1000_quizzes(): void
    {
        // Primary regression: 2,820 quizzes at perPage=25 = 113 pages, far past the app-side
        // 1000-page cap. A stateful mock advances endCursor on every call.
        $total  = 2820;
        $calls  = 0;
        $mock   = function (PendingRequest $pending) use (&$calls, $total) {
            $calls++;

            if ($calls > 200) {
                throw new \RuntimeException('allDefinitions walk did not terminate.');
            }

            $start = ($calls - 1) * 25;
            $nodes = [];

            for ($id = $start; $id < min($start + 25, $total); $id++) {
                $nodes[] = ['id' => (string) $id, 'name' => 'Quiz ' . $id, 'questions' => [
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    'nodes'    => [],
                ]];
            }

            $hasNext = ($start + 25) < $total;

            return $this->gqlResponse([
                'site' => ['quizzes' => [
                    'pageInfo' => [
                        'hasNextPage'     => $hasNext,
                        'endCursor'       => $hasNext ? 'cursor_' . ($start + 25) : null,
                        'hasPreviousPage' => false,
                        'startCursor'     => 'cursor_' . $start,
                    ],
                    'nodes'    => $nodes,
                ]],
            ]);
        };

        $this->mockGql([QuizDefinitions::class => $mock]);

        $index = $this->gql->quizzes->allDefinitions(per_page: 25);

        $this->assertCount($total, $index);
        $this->assertArrayHasKey('0', $index);
        $this->assertArrayHasKey('2819', $index);
        MockClient::getGlobal()->assertSentCount(113);
    }

    public function test_all_definitions_stops_on_non_advancing_outer_cursor(): void
    {
        $calls    = 0;
        $mock     = function (PendingRequest $pending) use (&$calls) {
            $calls++;

            if ($calls > 10) {
                throw new \RuntimeException('allDefinitions hung on a non-advancing outer cursor.');
            }

            return $this->gqlResponse([
                'site' => ['quizzes' => [
                    'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'same', 'hasPreviousPage' => false, 'startCursor' => 's'],
                    'nodes'    => [$this->gqlQuizDefinitionNode(['id' => 'quiz_' . $calls])],
                ]],
            ]);
        };

        $this->mockGql([QuizDefinitions::class => $mock]);

        $index = $this->gql->quizzes->allDefinitions();

        $this->assertCount(2, $index);
    }

    public function test_all_definitions_stops_on_non_advancing_questions_cursor(): void
    {
        $calls = 0;
        $mock  = function (PendingRequest $pending) use (&$calls) {
            $calls++;

            if ($calls > 10) {
                throw new \RuntimeException('allDefinitions hung on a non-advancing questions cursor.');
            }

            $vars = $pending->body()->all()['variables'];

            return $this->gqlResponse([
                'site' => ['quizzes' => [
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'outer_end', 'hasPreviousPage' => false, 'startCursor' => 's'],
                    'nodes'    => [$this->gqlQuizDefinitionNode([
                        'id'        => 'quiz_b',
                        'questions' => [
                            'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'same_qs'],
                            'nodes'    => [[
                                'id' => $vars['questionsAfter'] === null ? 'b1' : 'b2',
                                'prompt' => 'B', 'position' => 1, 'type' => 'MULTIPLE_CHOICE',
                                'choices' => ['pageInfo' => ['hasNextPage' => false, 'endCursor' => null], 'nodes' => []],
                            ]],
                        ],
                    ])],
                ]],
            ]);
        };

        $this->mockGql([QuizDefinitions::class => $mock]);

        $index = $this->gql->quizzes->allDefinitions();

        $this->assertCount(1, $index);
        $this->assertSame('quiz_b', $index['quiz_b']->id);
    }

    public function test_all_definitions_handles_quiz_vanishing_during_drain(): void
    {
        $mock = function (PendingRequest $pending) {
            $vars = $pending->body()->all()['variables'];

            if ($vars['questionsAfter'] !== null) {
                // Drain re-issue omits the target quiz entirely.
                return $this->gqlResponse([
                    'site' => ['quizzes' => [
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'outer_end', 'hasPreviousPage' => false, 'startCursor' => 's'],
                        'nodes'    => [],
                    ]],
                ]);
            }

            return $this->gqlResponse([
                'site' => ['quizzes' => [
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'outer_end', 'hasPreviousPage' => false, 'startCursor' => 's'],
                    'nodes'    => [$this->gqlQuizDefinitionNode([
                        'id'        => 'quiz_b',
                        'questions' => [
                            'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'b_cursor'],
                            'nodes'    => [[
                                'id' => 'b1', 'prompt' => 'B1', 'position' => 1, 'type' => 'MULTIPLE_CHOICE',
                                'choices' => ['pageInfo' => ['hasNextPage' => false, 'endCursor' => null], 'nodes' => []],
                            ]],
                        ],
                    ])],
                ]],
            ]);
        };

        $this->mockGql([QuizDefinitions::class => $mock]);

        $index = $this->gql->quizzes->allDefinitions();

        $this->assertCount(1, $index);
        $this->assertEquals(['b1'], array_map(fn ($q) => $q->id, $index['quiz_b']->questions));
        $this->assertTrue($index['quiz_b']->hasMoreQuestions);
    }

    public function test_all_definitions_handles_null_questions_connection(): void
    {
        $this->mockGql([
            QuizDefinitions::class => $this->gqlResponse([
                'site' => ['quizzes' => $this->gqlConnection([
                    $this->gqlQuizDefinitionNode(['id' => 'quiz_none', 'questions' => null]),
                ])],
            ]),
        ]);

        $index = $this->gql->quizzes->allDefinitions();

        $this->assertSame([], $index['quiz_none']->questions);
        $this->assertFalse($index['quiz_none']->hasMoreQuestions);
        $this->assertNull($index['quiz_none']->questionsEndCursor);
    }
}
