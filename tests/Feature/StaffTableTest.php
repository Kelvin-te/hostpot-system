<?php

namespace Tests\Feature;

use App\Http\Livewire\StaffTable;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_table_renders_rows(): void
    {
        Staff::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'phone' => '0700000001',
            'role' => 'admin',
            'password' => 'password123',
            'is_active' => true,
        ]);

        Livewire::test(StaffTable::class)
            ->assertSee('Test Admin')
            ->assertSee('admin@test.com');
    }
}
