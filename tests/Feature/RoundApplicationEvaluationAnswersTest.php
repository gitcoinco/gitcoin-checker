<?php

namespace Tests\Feature;

use App\Models\AccessControl;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationEvaluationQuestions;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoundApplicationEvaluationAnswersTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testUpsert()
    {
        $user = User::factory()->create();
        $accessControl = AccessControl::factory()->create();

        $round = Round::factory()->create();
        $application = RoundApplication::factory()->create(
            [
                'round_id' => $round->id,
            ]
        );
        $question = RoundApplicationEvaluationQuestions::factory()->create([
            'round_id' => $round->id,
        ]);

        $this->actingAs($user);

        // Mocking the request data
        $requestData = [
            'answers' => [
                'yes',
                'no',
            ],
            'notes' => 'Sample Note',
        ];

        $response = $this->json('POST', route('round.application.evaluation.answers.upsert', ['application' => $application->uuid]), $requestData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('round_application_evaluation_answers', [
            'round_id' => $application->round_id,
            'application_id' => $application->id,
            'user_id' => $user->id,
            'notes' => 'Sample Note',
        ]);
    }

    public function testIndex()
    {
        $user = User::factory()->create();
        $accessControl = AccessControl::factory();
        $round = Round::factory()->create();
        $application = RoundApplication::factory()->create(
            [
                'round_id' => $round->id,
            ]
        );
        $question = RoundApplicationEvaluationQuestions::factory()->create([
            'round_id' => $round->id,
        ]);

        $this->actingAs($user);

        $response = $this->json('GET', route('round.application.user.evaluation.index', ['application' => $application->uuid]));
        $response->assertStatus(200);
    }
}
