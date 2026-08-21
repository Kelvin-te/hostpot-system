<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_runs_against_dedicated_test_database(): void
    {
        $this->assertSame('wingufi_billing_test', DB::getDatabaseName());
    }
}
