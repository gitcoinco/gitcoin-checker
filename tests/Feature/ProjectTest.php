<?php

namespace Tests\Feature;

use App\Models\AccessControl;
use App\Models\Project;
use App\Models\Round;
use App\Models\RoundRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_can_i_load_a_project(): void
    {
        $user = User::factory()->create();
        $accessControl = AccessControl::factory()->create(
            [
                'eth_addr' => $user->eth_addr,
            ]
        );

        $this->actingAs($user);
        $project = Project::factory()->create();

        $response = $this->get(route('project.show', $project));

        $response->assertStatus(200);
    }
}
