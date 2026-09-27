<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditTrail;
use App\Models\InvoiceParent;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

/**
 * Who changed what, with old and new values.
 */
class ActivityLogTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser(['full_name' => 'Rahima Khatun']);
        $this->actingAs($this->user);
    }

    public function test_creates_changes_and_deletes_are_logged_with_the_user(): void
    {
        $this->post('/unit/create', ['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => '1'])->assertRedirect();
        $unit = Unit::query()->where('full_name', 'Litre')->sole();
        $this->post("/unit/update/$unit->id", ['full_name' => 'Litre', 'formal_name' => 'Ltr', 'decimal_place' => '1'])->assertRedirect();
        $this->post("/unit/delete/$unit->id")->assertRedirect();

        $log = Activity::query()->where('subject_type', Unit::class)->where('subject_id', $unit->id)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted'], $log->pluck('event')->all());
        $this->assertTrue($log->every(fn (Activity $a) => $a->causer_id === $this->user->id));
        $this->assertSame('Litre', $log[0]->attribute_changes['attributes']['full_name']);
        $this->assertSame(['formal_name' => 'Ltr'], $log[1]->attribute_changes['attributes']);
        $this->assertSame(['formal_name' => 'L'], $log[1]->attribute_changes['old']);
    }

    public function test_removing_a_document_is_logged(): void
    {
        $invoice = (new InvoiceParent)->forceFill(['patient' => 1, 'invoice_date' => now(), 'invoice_number' => 'INV#T-1', 'invoice_by' => 1, 'status' => 0]);
        $invoice->save();

        $this->post("/invoice/remove/$invoice->id")->assertRedirect('/invoice/admin');

        $entry = Activity::query()->where('subject_type', InvoiceParent::class)->where('event', 'updated')->sole();
        $this->assertSame(['status' => 2], $entry->attribute_changes['attributes']);
        $this->assertSame(['status' => 0], $entry->attribute_changes['old']);
    }

    public function test_password_changes_are_logged_without_the_hash(): void
    {
        $this->post("/user/edit/{$this->user->id}", ['password' => 'n3w-secret'])->assertRedirect('/user/admin');

        $entries = Activity::query()->where('subject_type', User::class)->where('subject_id', $this->user->id)->get();
        $this->assertContains('password changed', $entries->pluck('description')->all());
        $this->assertStringNotContainsString(sha1('n3w-secret'), $entries->toJson());
    }

    public function test_access_changes_are_logged(): void
    {
        $role = $this->group(4, 'Front office');

        $this->post("/userGroup/turnoff/$role->id", ['permission' => 'unit.admin'])->assertOk();

        $entry = Activity::query()->where('log_name', 'access')->latest('id')->firstOrFail();
        $this->assertSame('permissions revoked', $entry->description);
        $this->assertSame(['unit.admin'], $entry->properties['permissions']);
        $this->assertSame($role->id, (int) $entry->subject_id);
    }

    public function test_sign_ins_are_not_logged_twice(): void
    {
        AuditTrail::recordLogin($this->user->id);

        $this->assertSame(0, Activity::query()->where('subject_type', AuditTrail::class)->count());
    }

    public function test_the_audit_log_lists_changes_and_needs_its_permission(): void
    {
        $this->post('/unit/create', ['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => '1']);

        $this->get('/auditLog/admin')->assertOk()
            ->assertSee('Rahima Khatun')
            ->assertSee('<strong>full_name</strong>: Litre', false)
            ->assertSee('Unit');

        $entry = Activity::query()->where('log_name', 'data')->latest('id')->firstOrFail();
        $this->get("/auditLog/view/$entry->id")->assertOk()->assertSee('History of this record')->assertSee('Litre');

        $this->group(2)->revokePermissionTo('auditLog.admin');
        $this->get('/auditLog/admin')->assertRedirect('/site/noaccess');
    }
}
