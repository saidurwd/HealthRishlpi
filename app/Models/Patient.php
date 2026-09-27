<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Registered patient (`os_patient`).
 *
 * @property int $id
 * @property int $category_new
 * @property int $category
 * @property string|null $pat_id "PAT#2026-SEP-10489"
 * @property string $name
 * @property int|null $age
 * @property string|null $age_type Year|Month
 * @property string|null $birth_date
 * @property string|null $address
 * @property int|null $thana
 * @property int|null $district
 * @property int|null $country
 * @property string|null $ref_no
 * @property string|null $sex
 * @property string|null $blood_groop
 * @property string|null $marital_status
 * @property string|null $email
 * @property string|null $national_id
 * @property string|null $spouse
 * @property string|null $occupation
 * @property string|null $religion
 * @property string|null $village
 * @property string|null $post
 * @property string|null $mobile
 * @property string|null $emergency_name
 * @property string|null $emergency_relation
 * @property string|null $emergency_contact
 * @property int|null $patient_type
 * @property int|null $patient_grade
 * @property string|null $problem
 * @property string|null $referred
 * @property string|null $guardian_occupation
 * @property string|null $no_of_family_member
 * @property string|null $earning_member
 * @property string|null $earning_source
 * @property string|null $admission
 * @property string|null $created_on
 * @property int|null $created_by
 */
#[Table('patient', timestamps: false)]
#[Fillable([
    'category_new', 'category', 'ref_no', 'name', 'age', 'age_type', 'sex', 'birth_date', 'blood_groop', 'marital_status',
    'email', 'national_id', 'spouse', 'occupation', 'religion', 'address', 'village', 'post', 'thana', 'district', 'country',
    'mobile', 'emergency_name', 'emergency_relation', 'emergency_contact', 'patient_type', 'patient_grade', 'problem',
    'referred', 'guardian_occupation', 'no_of_family_member', 'earning_member', 'earning_source', 'admission',
])]
class Patient extends LegacyModel
{
    /**
     * The column's enum spells the negatives with U+2212 MINUS SIGN. The Yii
     * form posted "O-", which MySQL (non-strict) stored as '', so the values
     * here are the enum's own.
     */
    public const BLOOD_GROUPS = ["O\u{2212}" => 'O-', 'O+' => 'O+', "A\u{2212}" => 'A-', 'A+' => 'A+', "B\u{2212}" => 'B-', 'B+' => 'B+', "AB\u{2212}" => 'AB-', 'AB+' => 'AB+'];

    public const RELIGIONS = ['Muslim' => 'Muslim', 'Hindu' => 'Hindu', 'Christian' => 'Christian', 'Buddhist' => 'Buddhist', 'No Religion' => 'No Religion'];

    public const MARITAL_STATUSES = ['Married' => 'Married', 'Unmarried' => 'Unmarried', 'Others' => 'Others'];

    protected $attributes = ['age_type' => 'Year', 'sex' => 'Male', 'marital_status' => 'Unmarried'];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'category' => 'Sub Category',
            'category_new' => 'Category',
            'pat_id' => 'Patient ID',
            'ref_no' => 'Ref. No',
            'name' => 'Name',
            'age' => 'Age',
            'age_type' => 'Age Type',
            'sex' => 'Sex',
            'birth_date' => 'Date of Birth',
            'blood_groop' => 'Blood Group',
            'marital_status' => 'Marital Status',
            'email' => 'Email',
            'national_id' => 'National ID',
            'spouse' => 'Spouse',
            'occupation' => 'Occupation',
            'religion' => 'Religion',
            'address' => 'Address',
            'village' => 'Village',
            'post' => 'Post',
            'thana' => 'Thana',
            'district' => 'District',
            'country' => 'Country',
            'mobile' => 'Mobile',
            'emergency_name' => 'Guardian Name',
            'emergency_relation' => 'Relation',
            'emergency_contact' => 'Contact',
            'patient_type' => 'Patient Type',
            'patient_grade' => 'Patient Grade',
            'problem' => 'Problem',
            'referred' => 'Referred',
            'no_of_family_member' => 'Nr. of family member',
            'earning_member' => 'Earning Member',
            'guardian_occupation' => 'Guardian Occupation',
            'earning_source' => 'Earning Source',
            'admission' => 'Admission',
            'created_on' => 'Registration Date',
            'created_by' => 'Registration By',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'category_new' => ['required', 'integer'],
            'category' => ['required', 'integer'],
            'name' => ['required', 'max:150'],
            'admission' => ['required', 'max:50'],
            'age' => ['integer'],
            'thana' => ['integer'],
            'district' => ['integer'],
            'country' => ['integer'],
            'patient_type' => ['integer'],
            'patient_grade' => ['integer'],
            'email' => ['max:150'],
            'spouse' => ['max:150'],
            'occupation' => ['max:150'],
            'religion' => ['max:150'],
            'mobile' => ['max:150'],
            'emergency_name' => ['max:150'],
            'emergency_relation' => ['max:150'],
            'emergency_contact' => ['max:150'],
            'village' => ['max:150'],
            'post' => ['max:150'],
            'sex' => ['max:6'],
            'blood_groop' => ['max:5'],
            'marital_status' => ['max:9'],
            'national_id' => ['max:50'],
            'age_type' => ['max:50'],
            'ref_no' => ['max:50'],
            'no_of_family_member' => ['max:50'],
            'earning_member' => ['max:50'],
            'address' => ['max:250'],
            'referred' => ['max:250'],
            'guardian_occupation' => ['max:250'],
            'earning_source' => ['max:250'],
            'problem' => ['max:400'],
            'birth_date' => ['nullable'],
        ];
    }

    /** @return BelongsTo<PatientCategory, $this> */
    public function category0(): BelongsTo
    {
        return $this->belongsTo(PatientCategory::class, 'category');
    }

    /** @return BelongsTo<PatientCategoryNew, $this> */
    public function category_new0(): BelongsTo
    {
        return $this->belongsTo(PatientCategoryNew::class, 'category_new');
    }

    /** @return BelongsTo<Thana, $this> */
    public function thana0(): BelongsTo
    {
        return $this->belongsTo(Thana::class, 'thana');
    }

    /** @return BelongsTo<District, $this> */
    public function district0(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district');
    }

    /** @return BelongsTo<Country, $this> */
    public function country0(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /** @return BelongsTo<PatientGrade, $this> */
    public function grade0(): BelongsTo
    {
        return $this->belongsTo(PatientGrade::class, 'patient_grade');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasBirthDate(): bool
    {
        return ! in_array((string) $this->birth_date, ['', '0000-00-00', '0000-00-00 00:00:00'], true);
    }

    /**
     * "34 Years 2 Months 5 Days" from the birth date, else "<age> <age type>"
     * (Patient::getPatiantAge()).
     */
    public function ageText(): string
    {
        return $this->hasBirthDate() ? self::ageFromDate((string) $this->birth_date) : $this->age.' '.$this->age_type;
    }

    /**
     * Compact age for lists: "34 y", "3 y 2 m", "7 m", "12 d".
     */
    public function ageShort(): string
    {
        if (! $this->hasBirthDate()) {
            return $this->age === null ? '' : $this->age.' '.($this->age_type === 'Month' ? 'm' : 'y');
        }

        $diff = (new DateTime)->diff(new DateTime((string) $this->birth_date));

        return match (true) {
            $diff->y >= 5 => $diff->y.' y',
            $diff->y > 0 => $diff->y.' y'.($diff->m > 0 ? ' '.$diff->m.' m' : ''),
            $diff->m > 0 => $diff->m.' m',
            default => $diff->d.' d',
        };
    }

    /**
     * Address followed by thana and district (Patient::getPatiantAddress()).
     */
    public function fullAddress(): string
    {
        return $this->address
            .($this->thana !== null ? ', '.$this->thana0?->title : '')
            .($this->district !== null ? ', '.$this->district0?->title : '');
    }

    /**
     * Whole years since a date (Patient::getAgeYear()). An empty date is now.
     */
    public static function yearsSince(string $date): int
    {
        return (new DateTime)->diff(new DateTime($date))->y;
    }

    public static function ageFromDate(string $date): string
    {
        $diff = (new DateTime)->diff(new DateTime($date));

        return $diff->y.' Years '.$diff->m.' Months '.$diff->d.' Days';
    }

    /**
     * Birth date implied by an age (Patient::getAgeToDate()); null for 0.
     */
    public static function birthDateFromAge(mixed $age, ?string $type): ?string
    {
        if ((int) $age <= 0) {
            return null;
        }

        return date('Y-m-d', strtotime('-'.(int) $age.($type === 'Year' ? ' years' : ' months')));
    }

    /**
     * Next patient number: PAT#<year>-<MON>-<max id + 1> (Patient::autoPatientNumber()).
     */
    public static function nextNumber(): string
    {
        $maxId = DB::table('patient')->max('id');

        return 'PAT#'.date('Y').'-'.strtoupper(date('M')).'-'.($maxId ? (int) $maxId + 1 : 1);
    }
}
