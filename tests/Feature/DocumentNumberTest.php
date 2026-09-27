<?php

namespace Tests\Feature;

use App\Models\InvoiceParent;
use App\Models\User;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Document numbers are chosen under a named lock per sequence, so saves at
 * the same moment cannot get the same number.
 */
class DocumentNumberTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::connection('other_request')->selectOne('SELECT RELEASE_ALL_LOCKS() AS released');
        DocumentNumber::$timeout = 10;

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A second connection stands in for a concurrent request
        config(['database.connections.other_request' => config('database.connections.mariadb')]);
    }

    public function test_a_save_waits_for_the_one_in_progress(): void
    {
        DB::connection('other_request')->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [DocumentNumber::lockName('invoice')]);
        DocumentNumber::$timeout = 0;
        $ran = false;

        try {
            DocumentNumber::locked('invoice', function () use (&$ran) {
                $ran = true;
            });
            $this->fail('The lock should have been busy');
        } catch (RuntimeException $e) {
            $this->assertSame('The next invoice number is busy; please try again.', $e->getMessage());
        }

        $this->assertFalse($ran);

        // Other sequences are not held up
        $this->assertSame('ok', DocumentNumber::locked('prescription', fn () => 'ok'));
    }

    public function test_the_lock_is_released_after_the_save_even_when_it_fails(): void
    {
        $this->assertSame(5, DocumentNumber::locked('invoice', fn () => 5));

        try {
            DocumentNumber::locked('invoice', fn () => throw new RuntimeException('save failed'));
        } catch (RuntimeException) {
        }

        $free = DB::connection('other_request')->selectOne('SELECT IS_FREE_LOCK(?) AS free', [DocumentNumber::lockName('invoice')])->free;
        $this->assertSame(1, (int) $free);
    }

    public function test_the_newest_number_breaks_ties_on_the_id(): void
    {
        foreach (['INV#A-'.date('Y').'-7', 'INV#A-'.date('Y').'-8'] as $number) {
            (new InvoiceParent)->forceFill(['patient' => 1, 'invoice_date' => now(), 'invoice_number' => $number, 'invoice_by' => 1, 'status' => 0, 'created_on' => '2026-01-01 10:00:00'])->save();
        }

        $this->assertSame('INV#B-'.date('Y').'-9', InvoiceParent::nextNumber('b'));
    }

    public function test_invoice_creation_takes_the_lock(): void
    {
        $this->actingAs($this->makeUser(['group_id' => User::SUPER_GROUP]))->withSession(['currency' => '৳', 'login_name' => 'tester']);
        DB::connection('other_request')->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [DocumentNumber::lockName('invoice')]);
        DocumentNumber::$timeout = 0;
        DB::table('invoice')->insert(['parent' => null, 'servicetype' => 'Service', 'item' => 0, 'quantity' => 1, 'rate' => 10, 'amount' => 10, 'created_by' => auth()->id()]);

        $this->withoutExceptionHandling();
        $this->expectExceptionMessage('The next invoice number is busy');
        $this->post('/invoice/create', ['patient' => 1, 'payment_status' => 'Paid']);
    }
}
