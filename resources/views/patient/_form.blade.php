{{-- Patient fields (patient/form.blade.php) --}}
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-user text-primary"></i> Patient</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input name="name" :label="$record::label('name')" :value="$record->name" maxlength="150" placeholder="Full name" required autofocus />
                    </div>
                    <div class="col-md-3">
                        <x-form.select name="sex" :label="$record::label('sex')" :options="['Male' => 'Male', 'Female' => 'Female']" :value="$record->sex" empty="Select" />
                    </div>
                    <div class="col-md-3">
                        <x-form.input name="mobile" :label="$record::label('mobile')" :value="$record->mobile" maxlength="150" placeholder="01XXXXXXXXX" inputmode="tel" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="category_new" :label="$record::label('category_new')" :options="$categoriesNew" :value="$record->category_new" empty="Select a Category" required searchable />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="category" :label="$record::label('category')" :options="$categories" :value="$record->category" empty="Select a Sub Category" required searchable />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <x-form.input name="age" :label="$record::label('age')" :value="$record->age" maxlength="6" placeholder="Age" inputmode="numeric" />
                    </div>
                    <div class="col-md-2">
                        <x-form.select name="age_type" :label="html_entity_decode('&nbsp;')" :options="['Year' => 'Years', 'Month' => 'Months']" :value="$record->age_type" aria-label="Age in" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="birth_date" type="date" :label="$record::label('birth_date')" :value="$record->hasBirthDate() ? substr((string) $record->birth_date, 0, 10) : ''" />
                    </div>
                    <div class="col-md-3">
                        <x-form.select name="blood_groop" :label="$record::label('blood_groop')" :options="$record::BLOOD_GROUPS" :value="$record->blood_groop" empty="Unknown" />
                    </div>
                    <div class="col-12 form-text mt-n2 mb-3">Enter the age or the date of birth; the other is filled in from it.</div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <x-form.select name="admission" :label="$record::label('admission')" :options="['No' => 'No', 'Yes' => 'Yes']" :value="$record->admission" required />
                    </div>
                    <div class="col-md-3">
                        <x-form.select name="patient_grade" :label="$record::label('patient_grade')" :options="$grades" :value="$record->patient_grade" empty="Select a Grade" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="ref_no" :label="$record::label('ref_no')" :value="$record->ref_no" maxlength="50" placeholder="Reference No" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input name="problem" :label="$record::label('problem')" :value="$record->problem" maxlength="400" placeholder="Presenting problem" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="referred" :label="$record::label('referred')" :value="$record->referred" maxlength="250" placeholder="Referred by" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-map-marker text-primary"></i> Address</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input name="address" :label="$record::label('address')" :value="$record->address" maxlength="250" placeholder="House, road, area" />
                    </div>
                    <div class="col-md-3">
                        <x-form.input name="village" :label="$record::label('village')" :value="$record->village" maxlength="150" placeholder="Village" />
                    </div>
                    <div class="col-md-3">
                        <x-form.input name="post" :label="$record::label('post')" :value="$record->post" maxlength="150" placeholder="Post Office" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <x-form.select name="country" :label="$record::label('country')" :options="$countries" :value="$record->country" empty="Select a Country" />
                    </div>
                    <div class="col-md-4">
                        <x-form.select name="district" :label="$record::label('district')" :options="$districts" :value="$record->district" empty="Select a District" data-chained="#country" />
                    </div>
                    <div class="col-md-4">
                        <x-form.select name="thana" :label="$record::label('thana')" :options="$thanas" :value="$record->thana" empty="Select a Thana" data-chained="#district" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-id-card text-primary"></i> Personal</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <x-form.select name="marital_status" :label="$record::label('marital_status')" :options="$record::MARITAL_STATUSES" :value="$record->marital_status" />
                    </div>
                    <div class="col-6">
                        <x-form.select name="religion" :label="$record::label('religion')" :options="$record::RELIGIONS" :value="$record->religion" empty="Select" />
                    </div>
                </div>
                <x-form.input name="spouse" :label="$record::label('spouse')" :value="$record->spouse" maxlength="150" placeholder="Spouse" />
                <x-form.input name="occupation" :label="$record::label('occupation')" :value="$record->occupation" maxlength="150" placeholder="Occupation" />
                <x-form.input name="national_id" :label="$record::label('national_id')" :value="$record->national_id" maxlength="50" placeholder="National ID" />
                <x-form.input name="email" :label="$record::label('email')" :value="$record->email" maxlength="150" placeholder="Email" />
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-users text-primary"></i> Guardian</h3></div>
            <div class="card-body">
                <x-form.input name="emergency_name" :label="$record::label('emergency_name')" :value="$record->emergency_name" maxlength="150" placeholder="Guardian name" />
                <div class="row">
                    <div class="col-6">
                        <x-form.input name="emergency_relation" :label="$record::label('emergency_relation')" :value="$record->emergency_relation" maxlength="150" placeholder="Relation" />
                    </div>
                    <div class="col-6">
                        <x-form.input name="emergency_contact" :label="$record::label('emergency_contact')" :value="$record->emergency_contact" maxlength="150" placeholder="Contact" />
                    </div>
                </div>
                <x-form.input name="guardian_occupation" :label="$record::label('guardian_occupation')" :value="$record->guardian_occupation" maxlength="150" placeholder="Guardian occupation" />
                <div class="row">
                    <div class="col-6">
                        <x-form.input name="no_of_family_member" :label="$record::label('no_of_family_member')" :value="$record->no_of_family_member" maxlength="50" placeholder="Family members" />
                    </div>
                    <div class="col-6">
                        <x-form.input name="earning_member" :label="$record::label('earning_member')" :value="$record->earning_member" maxlength="50" placeholder="Earning members" />
                    </div>
                </div>
                <x-form.input name="earning_source" :label="$record::label('earning_source')" :value="$record->earning_source" maxlength="150" placeholder="Earning source" />
            </div>
        </div>
    </div>
</div>
