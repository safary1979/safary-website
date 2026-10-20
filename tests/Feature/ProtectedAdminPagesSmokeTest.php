<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ProtectedAdminPagesSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        umask(0002);

        parent::setUp();
    }

    public function test_primary_admin_pages_render_after_authentication(): void
    {
        $user = User::query()->find(1);

        $this->assertNotNull($user, 'Primary admin user id=1 is required for protected page smoke tests.');

        foreach ([
            '/admin',
            '/admin/results',
            '/admin/generated-autofon',
            '/admin/live-candidate-board',
        ] as $path) {
            $this
                ->actingAs($user)
                ->withSession([
                    'viewing_tenant' => 1,
                    'two_factor_verified_at' => now()->toDateTimeString(),
                ])
                ->get($path)
                ->assertOk();
        }
    }
}
