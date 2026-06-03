<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStructuredNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_is_built_from_name_parts(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'suffix' => 'Jr.',
        ]);

        $this->assertSame('Juan Dela Cruz Jr.', $user->name);
    }

    public function test_setting_name_splits_into_name_parts(): void
    {
        $user = User::factory()->make();
        $user->name = 'Maria Santos Lopez III';

        $this->assertSame('Maria', $user->first_name);
        $this->assertSame('Santos', $user->middle_name);
        $this->assertSame('Lopez', $user->last_name);
        $this->assertSame('III', $user->suffix);
    }
}
