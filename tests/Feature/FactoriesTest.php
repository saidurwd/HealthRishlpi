<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Department;
use App\Models\Disease;
use App\Models\Manufacturer;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Tests\TestCase;

/**
 * Factories must produce rows the live schema accepts.
 */
class FactoriesTest extends TestCase
{
    public function test_every_factory_creates_valid_rows(): void
    {
        foreach ([Batch::class, Department::class, Disease::class, Manufacturer::class, Patient::class, Product::class, Service::class, Store::class, Vendor::class, User::class] as $model) {
            $records = $model::factory()->count(2)->create();

            $this->assertCount(2, $model::query()->whereKey($records->modelKeys())->get(), $model);
        }
    }

    public function test_tree_factories_fill_path_and_alias(): void
    {
        $store = Store::factory()->create(['title' => 'Pharmacy']);

        $this->assertSame('0.'.$store->id, $store->fresh()->path);
        $this->assertSame('Pharmacy', $store->fresh()->alias);
    }

    public function test_product_comes_with_its_category_and_unit(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->category0);
        $this->assertNotNull($product->unit0);
    }
}
