<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_request_password_reset_link(): void
    {
        Notification::fake();

        $staff = Staff::factory()->create();

        $this->post(route('staff.password.email'), ['email' => $staff->email])
            ->assertRedirect();

        Notification::assertSentTo($staff, \App\Notifications\StaffResetPassword::class);
    }

    public function test_staff_password_can_be_reset_with_valid_token(): void
    {
        $staff = Staff::factory()->create();

        $token = Str::random(64);
        DB::table('staff_password_reset_tokens')->updateOrInsert(
            ['email' => $staff->email],
            ['email' => $staff->email, 'token' => Hash::make($token), 'created_at' => now()]
        );

        $this->post(route('staff.password.update'), [
            'token' => $token,
            'email' => $staff->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])
            ->assertRedirect(route('staff.login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPass123!', $staff->fresh()->password));
    }
}
