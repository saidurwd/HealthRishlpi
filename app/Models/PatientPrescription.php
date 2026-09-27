<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prescription written for a patient (`os_patient_prescription`); its
 * medicines are `os_prescription_medicine` rows.
 *
 * @property int $id
 * @property int $patient
 * @property string|null $pre_number "PRE#ADMIN-2026-2456"
 * @property int $diagnosis
 */
#[Table('patient_prescription', timestamps: false)]
#[Fillable(['diagnosis', 'cc', 'oe', 'bp', 'pulse', 'temp', 'advice', 'rx', 'admission'])]
class PatientPrescription extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'patient' => 'Patient',
            'pre_number' => 'Pre. No.',
            'diagnosis' => 'Diagnosis',
            'cc' => 'C/C',
            'oe' => 'O/E',
            'bp' => 'B/P',
            'pulse' => 'Pulse',
            'temp' => 'Temp',
            'advice' => 'Advice',
            'rx' => 'Prescription',
            'admission' => 'Admission',
            'created_on' => 'Created Date',
            'created_by' => 'Created By',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'diagnosis' => ['required', 'integer'],
            'cc' => ['max:250'],
            'oe' => ['max:250'],
            'bp' => ['max:250'],
            'pulse' => ['max:250'],
            'temp' => ['max:250'],
            'advice' => ['max:250'],
            'rx' => ['nullable'],
            'admission' => ['nullable'],
        ];
    }

    /** @return BelongsTo<Disease, $this> */
    public function diagnosis0(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'diagnosis');
    }

    /** @return HasMany<PrescriptionMedicine, $this> */
    public function medicines(): HasMany
    {
        return $this->hasMany(PrescriptionMedicine::class, 'parent');
    }

    /**
     * PRE#<LOGIN NAME>-<year>-<n>, n continuing from the newest prescription
     * of the same year (PatientPrescription::autoPrescriptionNumber()).
     */
    public static function nextNumber(string $loginName): string
    {
        return 'PRE#'.strtoupper($loginName).'-'.date('Y').'-'.self::nextSequence(
            static::query()->orderByDesc('created_on')->value('pre_number')
        );
    }

    /**
     * The "-<year>-<n>" counter shared by prescription and invoice numbers.
     */
    public static function nextSequence(?string $latest): int
    {
        if ($latest === null || $latest === '') {
            return 1;
        }

        $parts = explode('-', $latest);

        return ($parts[1] ?? null) == date('Y') ? (int) ($parts[2] ?? 0) + 1 : 1;
    }
}
