<div class="col-md-2"><x-form.select name="category_new" :label="''" :options="$categoryNewOptions" :value="$f['category_new']" empty="All Categories" class="form-select-sm" searchable /></div>
<div class="col-md-2"><x-form.select name="category" :label="''" :options="$subCategoryOptions" :value="$f['category']" empty="All Sub Categories" class="form-select-sm" searchable /></div>
