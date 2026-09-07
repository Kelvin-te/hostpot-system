<?php

namespace Tests\Feature;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Router;
use App\Models\User;
use App\Services\BillingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class BillingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_summary_calculates_correctly(): void
    {
        $router = Router::factory()->create();
        $package = Package::factory()->create(['router_id' => $router->id]);
        $user = User::factory()->create();

        PaymentTransaction::create([
            'checkout_request_id' => 'test1',
            'phone_number' => '0700000001',
            'amount' => 100.00,
            'account_reference' => '0700000001',
            'transaction_desc' => 'Test transaction 1',
            'status' => 'completed',
            'gateway' => 'mpesa',
            'type' => 'subscription',
            'package_id' => $package->id,
            'user_id' => $user->id,
            'router_id' => $router->id,
        ]);

        PaymentTransaction::create([
            'checkout_request_id' => 'test2',
            'phone_number' => '0700000002',
            'amount' => 200.00,
            'account_reference' => '0700000002',
            'transaction_desc' => 'Test transaction 2',
            'status' => 'completed',
            'gateway' => 'paystack',
            'type' => 'subscription',
            'package_id' => $package->id,
            'user_id' => $user->id,
            'router_id' => $router->id,
        ]);

        PaymentTransaction::create([
            'checkout_request_id' => 'test3',
            'phone_number' => '0700000003',
            'amount' => 50.00,
            'account_reference' => '0700000003',
            'transaction_desc' => 'Test transaction 3',
            'status' => 'failed',
            'gateway' => 'mpesa',
            'type' => 'subscription',
            'package_id' => $package->id,
        ]);

        $service = app(BillingReportService::class);
        $summary = $service->getRevenueSummary(Carbon::now()->subDay(), Carbon::now()->addDay());

        $this->assertEquals(300.00, $summary['total_revenue']);
        $this->assertEquals(2, $summary['total_transactions']);
        $this->assertEquals(150.00, $summary['average_transaction']);
        $this->assertEquals(1, $summary['unique_customers']);
    }

    public function test_invoice_number_generation(): void
    {
        $router = Router::factory()->create();
        $package = Package::factory()->create(['router_id' => $router->id]);

        $txn = PaymentTransaction::create([
            'checkout_request_id' => 'inv_test',
            'phone_number' => '0700000000',
            'amount' => 150.00,
            'account_reference' => '0700000000',
            'transaction_desc' => 'Invoice test',
            'status' => 'completed',
            'gateway' => 'mpesa',
            'type' => 'subscription',
            'package_id' => $package->id,
            'created_at' => Carbon::create(2026, 9, 2, 10, 0, 0),
        ]);

        $service = app(BillingReportService::class);
        $invoiceNumber = $service->generateInvoiceNumber($txn);

        $this->assertStringStartsWith('INV-' . $txn->created_at->format('Ymd') . '-', $invoiceNumber);
    }

    public function test_group_by_gateway(): void
    {
        PaymentTransaction::query()->delete();

        $package = Package::factory()->create();

        PaymentTransaction::create([
            'checkout_request_id' => 'g1',
            'phone_number' => '0700000001',
            'amount' => 100.00,
            'account_reference' => '0700000001',
            'transaction_desc' => 'Gateway test 1',
            'status' => 'completed',
            'gateway' => 'mpesa',
            'type' => 'subscription',
            'package_id' => $package->id,
        ]);

        PaymentTransaction::create([
            'checkout_request_id' => 'g2',
            'phone_number' => '0700000002',
            'amount' => 200.00,
            'account_reference' => '0700000002',
            'transaction_desc' => 'Gateway test 2',
            'status' => 'completed',
            'gateway' => 'paystack',
            'type' => 'subscription',
            'package_id' => $package->id,
        ]);

        $service = app(BillingReportService::class);
        $transactions = PaymentTransaction::completed()->get();
        $byGateway = $service->groupByGateway($transactions);

        $this->assertArrayHasKey('mpesa', $byGateway);
        $this->assertArrayHasKey('paystack', $byGateway);
        $this->assertEquals(100.00, $byGateway['mpesa']['revenue']);
        $this->assertEquals(200.00, $byGateway['paystack']['revenue']);
    }
}
