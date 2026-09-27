<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Disease;
use App\Models\District;
use App\Models\Instruction;
use App\Models\InvoiceParent;
use App\Models\LegacyModel;
use App\Models\Patient;
use App\Models\PatientCategory;
use App\Models\PatientCategoryNew;
use App\Models\PatientGrade;
use App\Models\PatientPrescription;
use App\Models\PrescriptionMedicine;
use App\Models\Product;
use App\Models\Thana;
use App\Models\TransectionStatus;
use App\Models\User;
use App\Support\Grid;
use App\Support\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Patient directory, prescriptions and the printable patient forms.
 */
class PatientController extends CrudController
{
    protected string $model = Patient::class;

    protected string $route = 'patient';

    protected string $plural = 'Patients';

    protected string $singular = 'Patient';

    /**
     * Category filters match the category titles (Patient::search() joined
     * category0 / category_new0).
     */
    protected function grid(): Grid
    {
        $query = Patient::query()
            ->select('patient.*')
            ->leftJoin('patient_category as category0', 'category0.id', '=', 'patient.category')
            ->leftJoin('patient_category_new as category_new0', 'category_new0.id', '=', 'patient.category_new')
            ->with('category0', 'category_new0', 'thana0', 'district0');

        $grid = Grid::for($query)->compare('id');

        foreach (['pat_id', 'ref_no', 'name', 'age_type', 'sex', 'birth_date', 'blood_groop', 'marital_status', 'email', 'national_id',
            'spouse', 'occupation', 'religion', 'address', 'mobile', 'emergency_name', 'emergency_relation', 'emergency_contact',
            'created_on', 'problem', 'referred', 'guardian_occupation', 'no_of_family_member', 'earning_member', 'earning_source', 'admission'] as $attribute) {
            $grid->compare($attribute, partial: true);
        }

        return $grid
            ->compare('age')
            ->compare('village')
            ->compare('post')
            ->compare('thana')
            ->compare('district')
            ->compare('country')
            ->compare('created_by')
            ->compare('patient_type')
            ->compare('patient_grade')
            ->compare('category', partial: true, column: 'category0.title')
            ->compare('category_new', partial: true, column: 'category_new0.title')
            ->sortable('category', 'patient.category')
            ->sortable('category_new', 'patient.category_new')
            ->defaultOrder('patient.id', 'desc');
    }

    public function view(Request $request, int $id): View
    {
        $patient = $this->find($id);

        $prescriptions = Grid::for(PatientPrescription::query()->where('patient', $patient->id)->with('diagnosis0'))
            ->compare('id');
        foreach (['pre_number', 'cc', 'oe', 'bp', 'pulse', 'temp', 'advice', 'rx', 'admission', 'created_on'] as $attribute) {
            $prescriptions->compare($attribute, partial: true);
        }
        $prescriptions->compare('diagnosis')->compare('created_by')
            ->defaultOrder('created_on', 'desc')->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize20'));

        return view('patient.view', [
            'record' => $patient,
            'prescriptions' => $prescriptions,
            'invoices' => InvoiceController::patientInvoicesGrid($patient->id),
            'diseases' => Disease::query()->orderBy('title')->pluck('title', 'id'),
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'invoiceStatuses' => TransectionStatus::options(TransectionStatus::INVOICE, userViewOnly: true),
        ]);
    }

    public function card(int $id): View
    {
        return view('patient.card', ['record' => $this->find($id)]);
    }

    public function rehabilitation(int $id): View
    {
        return view('patient.rehabilitation', ['record' => $this->find($id)]);
    }

    public function registration(int $id): View
    {
        return view('patient.registration', ['record' => $this->find($id)]);
    }

    /**
     * Printed prescription with its medicines and the invoices raised for it.
     */
    public function prescription(Request $request, int $id): View
    {
        $prescription = PatientPrescription::query()->find((int) $request->query('preid'));

        return view('patient.prescription', [
            'record' => $this->find($id),
            'prescription' => $prescription ?? new PatientPrescription,
            'invoices' => InvoiceParent::query()->where('prescription', (int) $request->query('preid'))
                ->orderByDesc('created_on')
                ->with(['lines' => fn ($query) => $query->with('item0.unit0', 'service0')])
                ->get(),
        ]);
    }

    /**
     * The prescription printout without diagnosis and pharmacy order, for handwriting.
     */
    public function preblank(Request $request, int $id): View
    {
        return view('patient.preblank', [
            'record' => $this->find($id),
            'prescription' => PatientPrescription::query()->find((int) $request->query('preid')) ?? new PatientPrescription,
        ]);
    }

    public function newprescription(Request $request, int $id): View|RedirectResponse
    {
        $patient = $this->find($id);
        $prescription = new PatientPrescription;

        if ($request->isMethod('post')) {
            $prescription->fill($this->validatedPrescription($request));
            $prescription->patient = $patient->id;
            $prescription->pre_number = PatientPrescription::nextNumber(User::loginName());
            $prescription->created_on = now()->format('Y-m-d G:i:s');
            $prescription->created_by = $request->user()->id;
            $prescription->save();

            PrescriptionMedicine::query()
                ->where(fn ($query) => $query->whereNull('parent')->orWhere('parent', 0))
                ->where('created_by', $request->user()->id)
                ->update(['parent' => $prescription->id]);

            return redirect()->route('patient.view', $patient->id)->with('success', 'Data was saved successfully');
        }

        return $this->prescriptionForm($request, $prescription, $patient);
    }

    public function editprescription(Request $request, int $id): View|RedirectResponse
    {
        $prescription = PatientPrescription::query()->find($id) ?? abort(404, 'The requested page does not exist.');

        if ($request->isMethod('post')) {
            $prescription->fill($this->validatedPrescription($request))->save();

            return redirect()->route('patient.admin')->with('success', 'Data was saved successfully');
        }

        return $this->prescriptionForm($request, $prescription, $this->find((int) $prescription->patient));
    }

    /**
     * Delete a prescription (its medicines stay, as in the Yii app).
     */
    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        return $this->deleteRecord($request, PatientPrescription::query()->find($id));
    }

    /**
     * Add a medicine line to the prescription being written (AJAX). Answers
     * with the new line's id.
     */
    public function addmedicine(Request $request): Response
    {
        $input = $request->validate(PrescriptionMedicine::rules(), [], PrescriptionMedicine::labelsFor(array_keys(PrescriptionMedicine::rules())));

        $line = new PrescriptionMedicine($input);
        // The dropdown posts the product id; the line keeps the product title
        $line->product = Product::query()->whereKey((int) $request->input('product'))->value('title');
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    public function removemedicine(Request $request, int $id): RedirectResponse|Response
    {
        return $this->deleteRecord($request, PrescriptionMedicine::query()->find($id));
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'categoriesNew' => self::twoLevelOptions(PatientCategoryNew::class),
            'categories' => self::twoLevelOptions(PatientCategory::class),
            'grades' => PatientGrade::query()->pluck('title', 'id'),
            'countries' => Country::query()->where('status', 'Active')->pluck('title', 'id'),
            'districts' => District::query()->where('status', 'Active')->orderBy('title')->get()
                ->map(fn ($row) => ['value' => $row->id, 'label' => $row->title, 'chain' => (string) $row->country])->all(),
            'thanas' => Thana::query()->where('status', 'Active')->orderBy('title')->get()
                ->map(fn ($row) => ['value' => $row->id, 'label' => $row->title, 'chain' => (string) $row->district])->all(),
        ];
    }

    /**
     * Patient number, age / birth date and registration stamp
     * (PatientController::actionCreate() / actionUpdate()).
     */
    protected function saving(LegacyModel $record, Request $request): void
    {
        /** @var Patient $record */
        if (! $record->exists) {
            $record->pat_id = Patient::nextNumber();

            // Yii took the age from the birth date even when none was entered,
            // which turned a typed age into 0; a missing date now comes from the age
            if ($record->hasBirthDate()) {
                $record->age = Patient::yearsSince((string) $record->birth_date);
            } else {
                $record->birth_date = Patient::birthDateFromAge($record->age, $record->age_type);
            }

            $record->created_on = now()->format('Y-m-d G:i:s');
            $record->created_by = $request->user()->id;

            return;
        }

        if (empty($record->age)) {
            $record->age = Patient::yearsSince((string) $record->birth_date);
        }
        if (! $record->hasBirthDate() && ! empty($record->age)) {
            $record->birth_date = Patient::birthDateFromAge($record->age, $record->age_type);
        }
    }

    /**
     * Roots (parent 0 or NULL) by title, each followed by its children by
     * title, indented (PatientCategory[New]::getPatientCategoryForm()).
     *
     * @param  class-string<PatientCategory|PatientCategoryNew>  $model
     * @return array<int, string>
     */
    public static function twoLevelOptions(string $model): array
    {
        $children = $model::query()->whereNotNull('parent')->where('parent', '>', 0)->orderBy('title')->get(['id', 'parent', 'title'])->groupBy('parent');
        $options = [];

        foreach ($model::query()->where(fn ($query) => $query->whereNull('parent')->orWhere('parent', 0))->orderBy('title')->get(['id', 'title']) as $root) {
            $options[$root->id] = $root->title;
            foreach ($children[$root->id] ?? [] as $child) {
                $options[$child->id] = str_repeat("\u{00A0}", 6).$child->title;
            }
        }

        return $options;
    }

    private function prescriptionForm(Request $request, PatientPrescription $prescription, Patient $patient): View
    {
        $lines = PrescriptionMedicine::query()
            ->when(
                $prescription->exists,
                fn ($query) => $query->where('parent', $prescription->id),
                fn ($query) => $query->where(fn ($query) => $query->where('parent', 0)->orWhereNull('parent'))->where('created_by', $request->user()->id),
            );

        return view('patient.prescription-form', [
            'prescription' => $prescription,
            'patient' => $patient,
            'lines' => Grid::for($lines)->compare('id')->paginate(PHP_INT_MAX),
            'products' => Stock::itemOptions(),
            'instructions' => Instruction::query()->where('status', 'Active')->pluck('title', 'title'),
            'diseases' => Disease::query()->pluck('title', 'id'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPrescription(Request $request): array
    {
        $rules = PatientPrescription::rules();

        return $request->validate($rules, [], PatientPrescription::labelsFor(array_keys($rules)));
    }

    private function deleteRecord(Request $request, ?Model $record): RedirectResponse|Response
    {
        abort_if($record === null, 404, 'The requested page does not exist.');

        try {
            $record->delete();
        } catch (QueryException $e) {
            return $this->deleteFailed($e);
        }

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('patient.admin')));
    }
}
