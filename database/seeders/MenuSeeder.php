<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $modifierGroups = $this->seedModifierGroups();
        $products = $this->seedProducts($categories, $modifierGroups);
        $this->attachToBranches($products);
    }

    /** @return array<string, Category> */
    protected function seedCategories(): array
    {
        $data = [
            ['name' => 'Hot Coffee', 'icon' => 'heroicon-o-fire'],
            ['name' => 'Cold Coffee', 'icon' => 'heroicon-o-cube'],
            ['name' => 'Tea & Others', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Pastries', 'icon' => 'heroicon-o-cake'],
            ['name' => 'Cakes', 'icon' => 'heroicon-o-gift'],
        ];

        $categories = [];
        foreach ($data as $i => $row) {
            $categories[$row['name']] = Category::firstOrCreate(
                ['slug' => Str::slug($row['name'])],
                array_merge($row, ['sort_order' => $i, 'status' => 'active']),
            );
        }

        return $categories;
    }

    /** @return array<string, ModifierGroup> */
    protected function seedModifierGroups(): array
    {
        $size = ModifierGroup::firstOrCreate(['slug' => 'size'], [
            'name' => 'Size',
            'selection_type' => 'single',
            'is_required' => true,
            'min_select' => 1,
            'max_select' => 1,
            'sort_order' => 1,
        ]);
        $this->options($size, [
            ['name' => 'Regular', 'price_delta' => 0, 'is_default' => true],
            ['name' => 'Large', 'price_delta' => 3.00],
        ]);

        $sugar = ModifierGroup::firstOrCreate(['slug' => 'sugar-level'], [
            'name' => 'Sugar Level',
            'selection_type' => 'single',
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => 2,
        ]);
        $this->options($sugar, [
            ['name' => 'No Sugar', 'price_delta' => 0],
            ['name' => 'Less Sugar', 'price_delta' => 0],
            ['name' => 'Standard', 'price_delta' => 0, 'is_default' => true],
        ]);

        $milk = ModifierGroup::firstOrCreate(['slug' => 'milk-type'], [
            'name' => 'Milk Type',
            'selection_type' => 'single',
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => 3,
        ]);
        $this->options($milk, [
            ['name' => 'Dairy', 'price_delta' => 0, 'is_default' => true],
            ['name' => 'Oat', 'price_delta' => 2.00],
            ['name' => 'Almond', 'price_delta' => 2.00],
            ['name' => 'Soy', 'price_delta' => 1.50],
        ]);

        $addons = ModifierGroup::firstOrCreate(['slug' => 'add-ons'], [
            'name' => 'Add-ons',
            'selection_type' => 'multiple',
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 4,
            'sort_order' => 4,
        ]);
        $this->options($addons, [
            ['name' => 'Extra Shot', 'price_delta' => 3.00],
            ['name' => 'Vanilla Syrup', 'price_delta' => 1.50],
            ['name' => 'Caramel Syrup', 'price_delta' => 1.50],
            ['name' => 'Whipped Cream', 'price_delta' => 2.00],
        ]);

        return compact('size', 'sugar', 'milk', 'addons');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function options(ModifierGroup $group, array $rows): void
    {
        foreach ($rows as $i => $row) {
            ModifierOption::firstOrCreate(
                ['modifier_group_id' => $group->id, 'name' => $row['name']],
                array_merge($row, ['sort_order' => $i]),
            );
        }
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, ModifierGroup>  $modifierGroups
     * @return list<Product>
     */
    protected function seedProducts(array $categories, array $modifierGroups): array
    {
        $catalogue = [
            ['Espresso', 'Hot Coffee', 6.00, 'SC-ESP', true],
            ['Americano', 'Hot Coffee', 8.00, 'SC-AMR', true],
            ['Cappuccino', 'Hot Coffee', 12.00, 'SC-CAP', true],
            ['Caffe Latte', 'Hot Coffee', 13.00, 'SC-LAT', true],
            ['Flat White', 'Hot Coffee', 13.00, 'SC-FLW', false],
            ['Iced Latte', 'Cold Coffee', 14.00, 'SC-ILA', true],
            ['Cold Brew', 'Cold Coffee', 15.00, 'SC-CBW', false],
            ['Iced Mocha', 'Cold Coffee', 16.00, 'SC-IMC', true],
            ['Earl Grey Tea', 'Tea & Others', 9.00, 'SC-ERG', false],
            ['Hot Chocolate', 'Tea & Others', 12.00, 'SC-HCH', false],
            ['Butter Croissant', 'Pastries', 8.00, 'SC-BCR', true],
            ['Pain au Chocolat', 'Pastries', 9.50, 'SC-PCH', false],
            ['Tiramisu Slice', 'Cakes', 18.00, 'SC-TIR', true],
            ['Basque Cheesecake', 'Cakes', 19.00, 'SC-BCC', true],
        ];

        $products = [];
        foreach ($catalogue as $i => [$name, $catName, $price, $sku, $featured]) {
            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $categories[$catName]->id,
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.Str::lower($sku),
                    'description' => "House-made {$name} crafted by Star Coffee baristas.",
                    'base_price' => $price,
                    'sst_applicable' => true,
                    'is_featured' => $featured,
                    'prep_time_minutes' => str_contains($catName, 'Coffee') ? 4 : 2,
                    'status' => 'active',
                    'sort_order' => $i,
                ],
            );

            $isDrink = str_contains($catName, 'Coffee') || $catName === 'Tea & Others';
            if ($isDrink) {
                $product->modifierGroups()->syncWithoutDetaching([
                    $modifierGroups['size']->id => ['sort_order' => 1],
                    $modifierGroups['sugar']->id => ['sort_order' => 2],
                    $modifierGroups['milk']->id => ['sort_order' => 3],
                    $modifierGroups['addons']->id => ['sort_order' => 4],
                ]);
            }

            $products[] = $product;
        }

        return $products;
    }

    /** @param  list<Product>  $products */
    protected function attachToBranches(array $products): void
    {
        $branches = Branch::all();
        foreach ($branches as $branch) {
            foreach ($products as $product) {
                $branch->products()->syncWithoutDetaching([
                    $product->id => ['is_available' => true],
                ]);

                $branch->stocks()->updateOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => 0, 'low_threshold' => 5, 'is_available' => true, 'track_quantity' => false],
                );
            }
        }
    }
}
