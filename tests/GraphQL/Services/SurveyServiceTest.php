<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use Carbon\Carbon;
use Saloon\Http\Faking\MockClient;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Choice;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Question;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses\SurveyResponse;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Responses\UserAnswer;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Surveys\Survey;
use WooNinja\ThinkificSaloon\GraphQL\DataTransferObjects\Users\User;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Surveys\Surveys;
use WooNinja\ThinkificSaloon\GraphQL\Requests\Surveys\UserSurveys;
use WooNinja\ThinkificSaloon\Tests\GraphQL\GraphQLTestCase;

class SurveyServiceTest extends GraphQLTestCase
{
    // -------------------------------------------------------------------------
    // surveys() — site survey list with nested questions/choices
    // -------------------------------------------------------------------------

    public function test_surveys_returns_survey_dtos(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveys()->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Survey::class, $items[0]);
        $this->assertEquals(601, $items[0]->id);
    }

    public function test_surveys_hydrates_name_and_selects_it(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode(['name' => 'Course feedback'])])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveys()->items());

        $this->assertSame('Course feedback', $items[0]->name);

        MockClient::getGlobal()->assertSent(
            fn (Surveys $request) => str_contains($request->body()->all()['query'], 'name')
        );
    }

    public function test_surveys_name_is_null_when_api_omits_it(): void
    {
        // Survey.name is nullable in the schema.
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        $this->assertNull(iterator_to_array($this->gql->surveys->surveys()->items())[0]->name);
    }

    public function test_surveys_passes_filter_and_omits_it_when_empty(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->surveys->surveys(filter: ['courseName' => 'Intro to PHP'])->items());
        iterator_to_array($this->gql->surveys->surveys()->items());

        $sent = array_map(
            fn ($r) => $r->getPendingRequest()->body()->all(),
            MockClient::getGlobal()->getRecordedResponses()
        );

        $this->assertSame(['courseName' => 'Intro to PHP'], $sent[0]['variables']['filter']);
        $this->assertStringContainsString('$filter: SurveysFilter', $sent[0]['query']);
        $this->assertStringContainsString('surveys(first: $first, after: $after, filter: $filter)', $sent[0]['query']);
        $this->assertNull($sent[1]['variables']['filter']);
    }

    public function test_surveys_maps_created_at_as_carbon(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveys()->items());

        $this->assertInstanceOf(Carbon::class, $items[0]->created_at);
        $this->assertEquals('2024-01-01', $items[0]->created_at->toDateString());
    }

    public function test_surveys_maps_questions(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveys()->items());

        $this->assertCount(1, $items[0]->questions);
        $this->assertInstanceOf(Question::class, $items[0]->questions[0]);
        $this->assertEquals(701, $items[0]->questions[0]->id);
        $this->assertEquals('MULTIPLE_CHOICE', $items[0]->questions[0]->questionType);
        $this->assertEquals(1, $items[0]->questions[0]->position);
        $this->assertEquals('How satisfied are you?', $items[0]->questions[0]->prompt);
    }

    public function test_surveys_maps_choices_within_questions(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveys()->items());
        $choices = $items[0]->questions[0]->choices;

        $this->assertCount(2, $choices);
        $this->assertInstanceOf(Choice::class, $choices[0]);
        $this->assertEquals(801, $choices[0]->id);
        $this->assertEquals('Very satisfied', $choices[0]->text);
        $this->assertEquals(1, $choices[0]->position);
        $this->assertEquals(802, $choices[1]->id);
    }

    public function test_surveys_terminates_at_last_page(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()], hasNextPage: false)],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->surveys->surveys() as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }

    public function test_surveys_returns_empty_when_none_exist(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveys()->items());

        $this->assertEmpty($items);
    }

    // -------------------------------------------------------------------------
    // surveysForUser() — user-scoped survey submissions
    // -------------------------------------------------------------------------

    public function test_surveys_for_user_returns_survey_response_dtos(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveysForUser(1)->items());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(SurveyResponse::class, $items[0]);
        $this->assertEquals(901, $items[0]->id);
        $this->assertEquals(601, $items[0]->survey_id);
    }

    public function test_surveys_for_user_maps_timestamps_as_carbon(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveysForUser(1)->items());

        $this->assertInstanceOf(Carbon::class, $items[0]->created_at);
        $this->assertInstanceOf(Carbon::class, $items[0]->completed_at);
        $this->assertEquals('2024-02-01', $items[0]->created_at->toDateString());
    }

    public function test_surveys_for_user_maps_user_dto(): void
    {
        $response = $this->gqlSurveyResponseNode([
            'user' => $this->gqlUserMinimal(['id' => 42, 'email' => 'dana@example.com']),
        ]);

        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$response])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveysForUser(42)->items());

        $this->assertInstanceOf(User::class, $items[0]->user);
        $this->assertEquals(42, $items[0]->user->id);
        $this->assertEquals('dana@example.com', $items[0]->user->email);
    }

    public function test_surveys_for_user_maps_user_answers(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveysForUser(1)->items());
        $answer = $items[0]->userAnswers[0];

        $this->assertInstanceOf(UserAnswer::class, $answer);
        $this->assertFalse($answer->skipped);
        $this->assertNull($answer->textResponse);
        $this->assertInstanceOf(Question::class, $answer->question);
        $this->assertEquals(701, $answer->question->id);
    }

    public function test_surveys_for_user_maps_choices_within_answers(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        $items  = iterator_to_array($this->gql->surveys->surveysForUser(1)->items());
        $choice = $items[0]->userAnswers[0]->choices[0];

        $this->assertInstanceOf(Choice::class, $choice);
        $this->assertEquals(801, $choice->id);
        $this->assertEquals('Very satisfied', $choice->text);
    }

    public function test_surveys_for_user_skipped_answer(): void
    {
        $response = $this->gqlSurveyResponseNode([
            'userAnswers' => [
                'nodes' => [[
                    'textResponse' => null,
                    'skipped'      => true,
                    'question'     => ['id' => 701],
                    'choices'      => [],
                ]],
            ],
        ]);

        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$response])],
            ]),
        ]);

        $items = iterator_to_array($this->gql->surveys->surveysForUser(1)->items());

        $this->assertTrue($items[0]->userAnswers[0]->skipped);
        $this->assertEmpty($items[0]->userAnswers[0]->choices);
    }

    // -------------------------------------------------------------------------
    // surveysForUser() — userAnswers cursor paging
    // -------------------------------------------------------------------------

    public function test_surveys_for_user_exposes_answer_page_info_when_more_answers_exist(): void
    {
        $node = $this->gqlSurveyResponseNode();
        $node['userAnswers']['pageInfo'] = ['hasNextPage' => true, 'endCursor' => 'ans_cursor_2'];

        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$node])],
            ]),
        ]);

        $response = iterator_to_array($this->gql->surveys->surveysForUser(1)->items())[0];

        $this->assertTrue($response->hasMoreAnswers);
        $this->assertEquals('ans_cursor_2', $response->answersEndCursor);
    }

    public function test_surveys_for_user_defaults_answer_page_info_when_absent(): void
    {
        // gqlSurveyResponseNode() has no userAnswers.pageInfo block.
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        $response = iterator_to_array($this->gql->surveys->surveysForUser(1)->items())[0];

        $this->assertFalse($response->hasMoreAnswers);
        $this->assertNull($response->answersEndCursor);
    }

    public function test_surveys_for_user_passes_answers_after_cursor_to_query(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->surveys->surveysForUser(1, user_answers: 5, answers_after: 'ans_cursor_2')->items());

        MockClient::getGlobal()->assertSent(function (UserSurveys $request) {
            $body = $request->body()->all();

            return $body['variables']['userAnswersFirst2'] === 5
                && $body['variables']['answersAfter'] === 'ans_cursor_2'
                && str_contains($body['query'], 'userAnswers(first: $userAnswersFirst2, after: $answersAfter)')
                && str_contains($body['query'], 'hasNextPage');
        });
    }

    public function test_surveys_for_user_reports_no_more_answers_on_last_answer_page(): void
    {
        $node = $this->gqlSurveyResponseNode();
        $node['userAnswers']['pageInfo'] = ['hasNextPage' => false, 'endCursor' => 'ans_cursor_end'];

        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$node])],
            ]),
        ]);

        $response = iterator_to_array($this->gql->surveys->surveysForUser(1)->items())[0];

        $this->assertFalse($response->hasMoreAnswers);
        $this->assertEquals('ans_cursor_end', $response->answersEndCursor);
    }

    public function test_surveys_for_user_handles_null_user_answers_connection(): void
    {
        // SurveySubmission.userAnswers is nullable in the schema.
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([
                    $this->gqlSurveyResponseNode(['userAnswers' => null]),
                ])],
            ]),
        ]);

        $response = iterator_to_array($this->gql->surveys->surveysForUser(1)->items())[0];

        $this->assertSame([], $response->userAnswers);
        $this->assertFalse($response->hasMoreAnswers);
        $this->assertNull($response->answersEndCursor);
    }

    public function test_surveys_for_user_sends_null_answers_after_by_default(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->surveys->surveysForUser(1)->items());

        MockClient::getGlobal()->assertSent(
            fn (UserSurveys $request) => $request->body()->all()['variables']['answersAfter'] === null
        );
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility — pre-existing call shapes must keep working
    // -------------------------------------------------------------------------

    public function test_bc_survey_dto_constructs_without_name(): void
    {
        $dto = new Survey(id: 601, created_at: Carbon::parse('2024-01-01'), questions: []);

        $this->assertNull($dto->name);
    }

    public function test_bc_survey_response_dto_constructs_without_new_arguments(): void
    {
        $dto = new SurveyResponse(
            id: 901,
            created_at: Carbon::parse('2024-01-01'),
            completed_at: null,
            userAnswers: [],
            survey_id: 601,
        );

        $this->assertNull($dto->user);
        $this->assertFalse($dto->hasMoreAnswers);
        $this->assertNull($dto->answersEndCursor);
    }

    public function test_bc_requests_construct_with_original_positional_arguments(): void
    {
        $surveys = (new Surveys(5, 6, 7))->body()->all()['variables'];
        $this->assertSame(5, $surveys['first']);
        $this->assertSame(6, $surveys['questionsFirst2']);
        $this->assertSame(7, $surveys['choicesFirst2']);
        $this->assertNull($surveys['filter']);

        $userSurveys = (new UserSurveys(1, 5, 7))->body()->all()['variables'];
        $this->assertSame(5, $userSurveys['first']);
        $this->assertSame(7, $userSurveys['userAnswersFirst2']);
        $this->assertNull($userSurveys['answersAfter']);
    }

    public function test_bc_service_methods_accept_original_positional_arguments(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection([$this->gqlSurveyResponseNode()])],
            ]),
        ]);

        $surveys   = iterator_to_array($this->gql->surveys->surveys(5, 6, 7)->items());
        $responses = iterator_to_array($this->gql->surveys->surveysForUser(1, 5, 7)->items());

        $this->assertInstanceOf(Survey::class, $surveys[0]);
        $this->assertInstanceOf(SurveyResponse::class, $responses[0]);
    }

    public function test_surveys_filter_strips_null_values(): void
    {
        $this->mockGql([
            Surveys::class => $this->gqlResponse([
                'site' => ['surveys' => $this->gqlConnection([$this->gqlSurveyNode()])],
            ]),
        ]);

        iterator_to_array($this->gql->surveys->surveys(filter: ['courseName' => null, 'completedAt' => ['gte' => '2024-01-01']])->items());

        MockClient::getGlobal()->assertSent(
            fn (Surveys $request) => $request->body()->all()['variables']['filter'] === ['completedAt' => ['gte' => '2024-01-01']]
        );
    }

    public function test_surveys_for_user_terminates_at_last_page(): void
    {
        $this->mockGql([
            UserSurveys::class => $this->gqlResponse([
                'site' => ['surveySubmissions' => $this->gqlConnection(
                    [$this->gqlSurveyResponseNode()], hasNextPage: false
                )],
            ]),
        ]);

        $pages = 0;
        foreach ($this->gql->surveys->surveysForUser(1) as $_page) {
            $pages++;
        }

        $this->assertEquals(1, $pages);
    }
}