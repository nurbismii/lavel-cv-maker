<?php

namespace Tests\Feature;

use Tests\TestCase;

class InternalVPeopleCvApiTest extends TestCase
{
    public function test_request_without_integration_token_is_rejected(): void
    {
        config()->set('services.vpeople.integration_token', 'secret-token');

        $this->postJson('/api/internal/vpeople/cv-data', [
            'hashes' => [str_repeat('a', 64)],
        ])->assertStatus(401);
    }

    public function test_invalid_hash_is_rejected_after_authentication(): void
    {
        config()->set('services.vpeople.integration_token', 'secret-token');

        $this->withToken('secret-token')
            ->postJson('/api/internal/vpeople/cv-data', [
                'hashes' => ['not-a-valid-hash'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hashes.0');
    }
}
