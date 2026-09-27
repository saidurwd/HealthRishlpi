<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invoice header (`os_invoice_parent`); lines are `os_invoice` rows.
 * status (TransectionStatus::INVOICE): 0 pending, 1 approved (stock
 * issued), 2 deleted.
 *
 * @property int $id
 * @property int|null $patient
 * @property int|null $prescription
 * @property string $invoice_date
 * @property string $invoice_number "INV#ADMIN-2026-10340"
 * @property int $invoice_by
 * @property string|null $total_amount
 * @property int $status
 * @property string|null $payment_status Paid|Unpaid
 */
#[Table('invoice_parent', timestamps: false)]
#[Fillable(['patient', 'prescription', 'patient_category_new', 'patient_category', 'comments', 'status', 'payment_status'])]
class InvoiceParent extends LegacyModel
{
    public const PAYMENT_STATUSES = ['Paid' => 'Paid', 'Unpaid' => 'Unpaid'];

    protected $attributes = ['status' => 0, 'payment_status' => 'Unpaid'];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'patient' => 'Patient',
            'prescription' => 'Prescription',
            'invoice_date' => 'Date',
            'invoice_number' => 'Invoice#',
            'invoice_by' => 'Invoice By',
            'total_amount' => 'Amount',
            'patient_category' => 'Sub Category',
            'patient_category_new' => 'Category',
            'comments' => 'Comments',
            'status' => 'Status',
            'payment_status' => 'Payment Status',
            'created_on' => 'Created On',
            'created_by' => 'Created By',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'patient' => ['integer'],
            'prescription' => ['integer'],
            'patient_category_new' => ['integer'],
            'patient_category' => ['integer'],
            'status' => ['integer'],
            'payment_status' => ['max:100'],
            'comments' => ['nullable'],
        ];
    }

    /** @return HasMany<Invoice, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient0(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient');
    }

    /** @return BelongsTo<User, $this> */
    public function invoiceBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invoice_by');
    }

    /** Update / delete buttons: not for approved or deleted invoices */
    public function isEditable(): bool
    {
        return ! in_array((int) $this->status, [1, 2], true);
    }

    /** Rollback button: deleted invoices only */
    public function canRollback(): bool
    {
        return ! in_array((int) $this->status, [0, 1], true);
    }

    /** Special edit button: approved invoices only */
    public function canSpecialEdit(): bool
    {
        return (int) $this->status === 1;
    }

    /**
     * INV#<LOGIN NAME>-<year>-<n> (InvoiceParent::generateInvoiceNumber()).
     */
    public static function nextNumber(string $loginName): string
    {
        return 'INV#'.strtoupper($loginName).'-'.date('Y').'-'.PatientPrescription::nextSequence(
            static::query()->orderByDesc('created_on')->value('invoice_number')
        );
    }
}
