<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\Patient;
use App\Models\PatientCategory;
use App\Models\PatientCategoryNew;
use App\Models\PatientPrescription;
use App\Models\PrescriptionMedicine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Tests\TestCase;

/**
 * Patient directory, prescriptions and printouts.
 */
class PatientTest extends TestCase
{
    private PatientCategoryNew $category;

    private PatientCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->makeUser());
        $this->category = PatientCategoryNew::create(['title' => 'Adult', 'alias' => 'Adult']);
        $this->subCategory = PatientCategory::create(['title' => 'General', 'alias' => 'General']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return array_merge([
            'category_new' => $this->category->id, 'category' => $this->subCategory->id, 'ref_no' => '', 'name' => 'Rahim Uddin',
            'age' => '', 'age_type' => 'Year', 'birth_date' => '', 'blood_groop' => '', 'patient_grade' => '', 'admission' => 'No',
            'problem' => '', 'referred' => '', 'marital_status' => 'Unmarried', 'email' => '', 'national_id' => '', 'spouse' => '',
            'occupation' => '', 'religion' => '', 'mobile' => '0171', 'address' => 'Satkhira', 'country' => '', 'sex' => 'Male',
            'village' => '', 'post' => '',
        ], $overrides);
    }

    public function test_create_validates_and_numbers_the_patient(): void
    {
        $this->from('/patient/create')->post('/patient/create', $this->input(['category_new' => '', 'category' => '', 'name' => '', 'admission' => '']))
            ->assertSessionHasErrors([
                'category_new' => 'Category cannot be blank.',
                'category' => 'Sub Category cannot be blank.',
                'name' => 'Name cannot be blank.',
                'admission' => 'Admission cannot be blank.',
            ]);

        $response = $this->post('/patient/create', $this->input(['age' => '30']))
            ->assertSessionHas('success', 'Data was saved successfully');

        $patient = Patient::query()->where('name', 'Rahim Uddin')->firstOrFail();
        // Straight to the new patient's page, ready for a prescription or an invoice
        $response->assertRedirect("/patient/view/$patient->id");
        $this->assertMatchesRegularExpression('/^PAT#'.date('Y').'-'.strtoupper(date('M')).'-\d+$/', $patient->pat_id);
        // An age without a birth date gives the birth date (Yii turned the age into 0 here)
        $this->assertSame(30, $patient->age);
        $this->assertSame(date('Y-m-d', strtotime('-30 years')), $patient->birth_date);
        $this->assertSame(auth()->id(), $patient->created_by);
    }

    public function test_create_takes_the_age_from_the_birth_date_and_keeps_negative_blood_groups(): void
    {
        $birth = date('Y-m-d', strtotime('-12 years -2 months'));

        $this->post('/patient/create', $this->input(['age' => '99', 'birth_date' => $birth, 'blood_groop' => "B\u{2212}"]))->assertRedirect();

        $patient = Patient::query()->where('name', 'Rahim Uddin')->firstOrFail();
        $this->assertSame(12, $patient->age);
        $this->assertSame("B\u{2212}", $patient->blood_groop);
        $this->get("/patient/view/$patient->id")->assertOk()->assertSee('12 Years 2 Months');
    }

    public function test_update_fills_the_birth_date_from_the_age(): void
    {
        $patient = Patient::create($this->input());

        $this->post("/patient/update/$patient->id", $this->input(['age' => '6', 'age_type' => 'Month']))->assertRedirect("/patient/view/$patient->id");

        $this->assertSame(date('Y-m-d', strtotime('-6 months')), $patient->fresh()->birth_date);
    }

    public function test_grid_filters_categories_by_title(): void
    {
        Patient::create($this->input(['name' => 'Karim']));
        $child = PatientCategoryNew::create(['title' => 'Child', 'alias' => 'Child']);
        Patient::create($this->input(['name' => 'Lima', 'category_new' => $child->id]));

        $this->get('/patient/admin?Patient[category_new]=Chi')->assertSee('Lima')->assertDontSee('Karim');
    }

    public function test_prescription_with_medicines(): void
    {
        $patient = Patient::create($this->input());
        $disease = Disease::create(['title' => 'Fever']);
        $product = Product::create(['category' => ProductCategory::create(['title' => 'Tablets'])->id, 'title' => 'Napa', 'unit' => Unit::create(['full_name' => 'Piece', 'formal_name' => 'pcs', 'decimal_place' => 0])->id]);

        $this->get("/patient/newprescription/$patient->id")->assertOk()->assertSee('prescription-workspace');
        $this->getJson('/patient/products?q=nap')->assertOk()->assertJsonPath('0.text', 'Napa [pcs]');

        $this->post('/patient/addmedicine', ['parent' => 0, 'product' => $product->id, 'instruction' => '1+0+1 AFTER MEAL', 'no_of_days' => '5'])->assertOk();
        $line = PrescriptionMedicine::query()->firstOrFail();
        $this->assertSame('Napa', $line->product);
        $this->get("/patient/newprescription/$patient->id")->assertSee('1+0+1 AFTER MEAL');
        $this->getJson('/patient/medicines')->assertOk()->assertJsonPath('0.product', 'Napa')->assertJsonPath('0.days', '5');

        $this->from("/patient/newprescription/$patient->id")->post("/patient/newprescription/$patient->id", ['diagnosis' => ''])
            ->assertSessionHasErrors(['diagnosis' => 'Diagnosis cannot be blank.']);

        $this->post("/patient/newprescription/$patient->id", ['diagnosis' => $disease->id, 'cc' => 'Headache', 'bp' => '120/80'])
            ->assertRedirect("/patient/view/$patient->id");

        $prescription = PatientPrescription::query()->firstOrFail();
        $this->assertSame('PRE#TESTER-'.date('Y').'-1', $prescription->pre_number);
        $this->assertSame($prescription->id, $line->fresh()->parent);

        $this->get("/patient/view/$patient->id")->assertSee($prescription->pre_number)->assertSee('Fever');
        $this->get("/patient/prescription/$patient->id?preid=$prescription->id")->assertOk()->assertSee('PHARMACY ORDER')->assertSee('Napa')->assertSee('Headache');
        $this->get("/patient/preblank/$patient->id?preid=$prescription->id")->assertOk()->assertDontSee('PHARMACY ORDER');

        $this->post("/patient/editprescription/$prescription->id", ['diagnosis' => $disease->id, 'cc' => 'Cough'])->assertRedirect("/patient/view/$patient->id");
        $this->getJson("/patient/medicines?parent=$prescription->id")->assertOk()->assertJsonPath('0.product', 'Napa');
        $this->assertSame('Cough', $prescription->fresh()->cc);

        $this->post("/patient/removemedicine/$line->id", ['ajax' => 'prescription-medicine-grid'])->assertNoContent();
        $this->post("/patient/remove/$prescription->id", ['ajax' => 'prescription-grid'])->assertNoContent();
        $this->assertNull($prescription->fresh());
    }

    public function test_printouts(): void
    {
        $patient = Patient::create($this->input(['age' => 40]));
        $patient->forceFill(['pat_id' => 'PAT#X-1'])->save();

        $this->get("/patient/card/$patient->id")->assertOk()->assertSee('Health Card')->assertSee('PAT#X-1');
        $this->get("/patient/rehabilitation/$patient->id")->assertOk()->assertSee('Patient Particular');
        $this->get("/patient/registration/$patient->id")->assertOk()->assertSee('Patient Registration Form')->assertSee('40 Year');
    }

    public function test_list_search_matches_name_pat_id_mobile_or_national_id(): void
    {
        $patient = Patient::create($this->input(['name' => 'Karim', 'mobile' => '01899000111', 'national_id' => '19901234567']));
        $patient->forceFill(['pat_id' => 'PAT#2026-SEP-55'])->save();
        Patient::create($this->input(['name' => 'Lima']));

        foreach (['kar', '2026-SEP-55', '0189900', '1990123'] as $term) {
            $this->get('/patient/admin?Patient[name]='.urlencode($term))->assertOk()->assertSee('Karim')->assertDontSee('Lima');
        }
    }

    public function test_patient_page_summarises_visits_and_offers_the_next_steps(): void
    {
        $patient = Patient::create($this->input(['blood_groop' => 'A+', 'mobile' => '01700000000']));
        $prescription = PatientPrescription::query()->create(['diagnosis' => Disease::create(['title' => 'Fever'])->id]);
        $prescription->forceFill(['patient' => $patient->id, 'pre_number' => 'PRE#T-1', 'bp' => '120/80', 'created_on' => now()])->save();

        $this->get("/patient/view/$patient->id")->assertOk()
            ->assertSee('PRE#T-1')
            ->assertSee('120/80')
            ->assertSee('A+')
            ->assertSee(route('invoice.create', ['patient' => $patient->id]), false)
            ->assertSee(route('patient.newprescription', $patient->id), false);

        // The invoice screen opens with the patient chosen
        $this->get('/invoice/create?patient='.$patient->id)->assertOk()->assertSee('"id":'.$patient->id.',"text":"Rahim Uddin', false);
    }

    public function test_registration_form_offers_every_field_including_the_age_unit(): void
    {
        $page = $this->get('/patient/create')->assertOk();

        // The age unit select used to be emitted as a raw <x-form.select> tag
        $page->assertSee('<select id="age_type" name="age_type"', false);
        $this->assertStringNotContainsString('<x-', $page->getContent());
        foreach (['name', 'sex', 'mobile', 'category_new', 'category', 'age', 'birth_date', 'blood_groop', 'admission', 'address', 'district', 'thana', 'national_id', 'emergency_name'] as $field) {
            $page->assertSee('name="'.$field.'"', false);
        }
    }
}
