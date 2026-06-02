<?php

namespace WooNinja\ThinkificSaloon\Tests\GraphQL\Services;

use Carbon\Carbon;
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