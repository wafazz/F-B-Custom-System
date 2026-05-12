<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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
            ['name' => 'Specialty Drinks', 'icon' => 'heroicon-o-sparkles'],
            ['name' => 'Tea & Others', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Pastries', 'icon' => 'heroicon-o-cake'],
            ['name' => 'Cakes', 'icon' => 'heroicon-o-gift'],
            ['name' => 'Breakfast', 'icon' => 'heroicon-o-sun'],
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
            // Hot Coffee
            ['Espresso', 'Hot Coffee', 6.00, 'SC-ESP', false, 'Concentrated single shot of our signature house blend.'],
            ['Double Espresso', 'Hot Coffee', 8.00, 'SC-DES', false, 'Two shots of bold, syrupy espresso — for the purists.'],
            ['Americano', 'Hot Coffee', 8.00, 'SC-AMR', true, 'Espresso lengthened with hot water for a smooth, full-bodied cup.'],
            ['Cappuccino', 'Hot Coffee', 12.00, 'SC-CAP', true, 'Equal parts espresso, steamed milk and velvety foam. The classic.'],
            ['Caffe Latte', 'Hot Coffee', 13.00, 'SC-LAT', true, 'Smooth espresso topped with silky steamed milk and a light foam crown.'],
            ['Flat White', 'Hot Coffee', 13.00, 'SC-FLW', false, 'Double ristretto with micro-foamed milk — strong, silky, no fluff.'],
            ['Caffe Mocha', 'Hot Coffee', 14.00, 'SC-MCH', true, 'Espresso, steamed milk and house Belgian chocolate, finished with cream.'],
            ['Caramel Macchiato', 'Hot Coffee', 14.50, 'SC-CMC', true, 'Vanilla-sweetened milk marked with espresso and caramel drizzle.'],
            ['Cortado', 'Hot Coffee', 12.00, 'SC-CRT', false, '1:1 espresso and warm milk — bright, balanced, no foam.'],

            // Cold Coffee
            ['Iced Americano', 'Cold Coffee', 9.00, 'SC-IAM', false, 'Long espresso over ice for a crisp, clean wake-up.'],
            ['Iced Latte', 'Cold Coffee', 14.00, 'SC-ILA', true, 'Chilled espresso and cold milk over ice — our signature easy-drinker.'],
            ['Cold Brew', 'Cold Coffee', 15.00, 'SC-CBW', true, 'Steeped 18 hours for a low-acid, naturally sweet cold brew.'],
            ['Iced Mocha', 'Cold Coffee', 16.00, 'SC-IMC', true, 'Cold espresso, milk and Belgian chocolate — dessert in a cup.'],
            ['Nitro Cold Brew', 'Cold Coffee', 17.00, 'SC-NCB', true, 'Nitrogen-infused cold brew with a creamy stout-style cascade.'],
            ['Affogato', 'Cold Coffee', 15.00, 'SC-AFG', false, 'Double espresso poured over a scoop of Madagascan vanilla gelato.'],

            // Specialty Drinks
            ['Matcha Latte', 'Specialty Drinks', 15.00, 'SC-MTC', true, 'Ceremonial-grade Uji matcha whisked with warm milk.'],
            ['Iced Matcha Latte', 'Specialty Drinks', 16.00, 'SC-IMT', true, 'Cold matcha with milk over ice — vibrant, earthy, refreshing.'],
            ['Spanish Latte', 'Specialty Drinks', 15.00, 'SC-SPL', false, 'Espresso, steamed milk and a touch of sweetened condensed milk.'],
            ['Pistachio Latte', 'Specialty Drinks', 17.00, 'SC-PIS', true, 'Roasted pistachio syrup with espresso and milk — nutty and indulgent.'],
            ['Rose Latte', 'Specialty Drinks', 16.00, 'SC-ROS', false, 'Floral house-made rose syrup with steamed milk and a single shot.'],
            ['Dirty Chai', 'Specialty Drinks', 14.00, 'SC-DCH', false, 'Spiced chai latte with a shot of espresso for extra punch.'],

            // Tea & Others
            ['Earl Grey Tea', 'Tea & Others', 9.00, 'SC-ERG', false, 'Single-origin Ceylon black tea with bergamot oil.'],
            ['English Breakfast', 'Tea & Others', 9.00, 'SC-EBT', false, 'Robust morning blend — pairs perfectly with our pastries.'],
            ['Chamomile Tea', 'Tea & Others', 9.00, 'SC-CHM', false, 'Caffeine-free Egyptian chamomile, gently floral.'],
            ['Hot Chocolate', 'Tea & Others', 13.00, 'SC-HCH', true, 'Belgian dark chocolate melted into steamed milk, topped with marshmallows.'],
            ['Iced Lemon Tea', 'Tea & Others', 9.00, 'SC-ILT', false, 'Fresh-brewed black tea with lemon and a hint of honey.'],

            // Pastries
            ['Butter Croissant', 'Pastries', 8.00, 'SC-BCR', true, 'Hand-laminated with French butter — flaky outside, soft inside.'],
            ['Pain au Chocolat', 'Pastries', 9.50, 'SC-PCH', true, 'Buttery laminated pastry wrapped around two batons of dark chocolate.'],
            ['Almond Croissant', 'Pastries', 10.00, 'SC-ALC', false, 'Twice-baked croissant filled with frangipane and topped with almonds.'],
            ['Cinnamon Roll', 'Pastries', 11.00, 'SC-CIN', true, 'Soft brioche swirled with cinnamon-sugar and topped with cream cheese glaze.'],
            ['Blueberry Muffin', 'Pastries', 8.50, 'SC-BLM', false, 'Generously studded with fresh blueberries and a cinnamon crumble top.'],
            ['Banana Walnut Bread', 'Pastries', 8.00, 'SC-BNW', false, 'Moist banana loaf with toasted walnuts — house-baked daily.'],
            ['Glazed Donut', 'Pastries', 7.00, 'SC-GDN', false, 'Pillowy yeast donut with a thin vanilla glaze.'],

            // Cakes
            ['Tiramisu Slice', 'Cakes', 18.00, 'SC-TIR', true, 'Espresso-soaked savoiardi layered with mascarpone and dusted cocoa.'],
            ['Basque Cheesecake', 'Cakes', 19.00, 'SC-BCC', true, 'Burnt-top, custardy Basque-style cheesecake — rich and barely sweet.'],
            ['Chocolate Lava Cake', 'Cakes', 17.00, 'SC-LVA', true, 'Warm valrhona chocolate cake with a molten dark chocolate centre.'],
            ['Carrot Cake', 'Cakes', 16.00, 'SC-CRT2', false, 'Spiced layered carrot cake with walnut and cream cheese frosting.'],
            ['Lemon Tart', 'Cakes', 15.00, 'SC-LMT', false, 'Buttery shortcrust filled with sharp Sicilian lemon curd.'],
            ['Red Velvet Slice', 'Cakes', 17.00, 'SC-RVL', false, 'Cocoa-tinted sponge layered with classic cream cheese frosting.'],

            // Breakfast
            ['Avocado Toast', 'Breakfast', 18.00, 'SC-AVT', true, 'Smashed avocado on sourdough with chili flakes, lemon and pepitas.'],
            ['Eggs Benedict', 'Breakfast', 24.00, 'SC-EGB', true, 'Poached eggs and turkey ham on a toasted muffin with hollandaise.'],
            ['Smoked Salmon Bagel', 'Breakfast', 22.00, 'SC-SSB', false, 'House-cured salmon, cream cheese, capers and red onion on a sesame bagel.'],
            ['Acai Bowl', 'Breakfast', 19.00, 'SC-ACI', false, 'Frozen acai blend topped with granola, banana, berries and honey.'],
            ['Croque Madame', 'Breakfast', 23.00, 'SC-CRM', false, 'Pan-grilled ham and gruyère sandwich with béchamel and a sunny egg.'],
        ];

        $drinkCategories = ['Hot Coffee', 'Cold Coffee', 'Specialty Drinks', 'Tea & Others'];

        $products = [];
        foreach ($catalogue as $i => [$name, $catName, $price, $sku, $featured, $description]) {
            $imagePath = $this->ensureProductImage($sku, $name, $catName);

            $attrs = [
                'category_id' => $categories[$catName]->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower($sku),
                'description' => $description,
                'base_price' => $price,
                'sst_applicable' => true,
                'is_featured' => $featured,
                'prep_time_minutes' => in_array($catName, $drinkCategories, true) ? 4 : 2,
                'status' => 'active',
                'sort_order' => $i,
            ];
            if ($imagePath !== null) {
                $attrs['image'] = $imagePath;
            }

            $product = Product::updateOrCreate(['sku' => $sku], $attrs);

            if (in_array($catName, $drinkCategories, true)) {
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

    /** Download a themed cafe photo for this product if we don't have one yet. */
    protected function ensureProductImage(string $sku, string $name, string $catName): ?string
    {
        $relative = 'products/'.Str::lower(Str::slug($sku)).'.jpg';
        $full = storage_path('app/public/'.$relative);

        if (file_exists($full) && filesize($full) > 5000) {
            return $relative;
        }

        if (! is_dir(dirname($full))) {
            @mkdir(dirname($full), 0755, true);
        }

        $keyword = $this->imageKeywordFor($name, $catName);
        $url = "https://loremflickr.com/600/400/{$keyword}";

        try {
            $response = Http::timeout(20)->withOptions(['allow_redirects' => true])->get($url);
            if (! $response->successful()) {
                return null;
            }
            $body = $response->body();
            if (strlen($body) < 5000) {
                return null;
            }
            file_put_contents($full, $body);

            return $relative;
        } catch (Throwable) {
            return null;
        }
    }

    protected function imageKeywordFor(string $name, string $catName): string
    {
        $map = [
            'Espresso' => 'espresso', 'Americano' => 'americano',
            'Cappuccino' => 'cappuccino', 'Latte' => 'latte',
            'Flat White' => 'flat,white,coffee', 'Mocha' => 'mocha',
            'Macchiato' => 'macchiato', 'Cortado' => 'coffee,cortado',
            'Cold Brew' => 'cold,brew', 'Nitro' => 'nitro,coffee',
            'Affogato' => 'affogato', 'Matcha' => 'matcha,latte',
            'Pistachio' => 'pistachio,latte', 'Rose' => 'rose,latte',
            'Spanish' => 'spanish,latte', 'Dirty Chai' => 'chai,latte',
            'Earl Grey' => 'tea,earl,grey', 'English Breakfast' => 'tea',
            'Chamomile' => 'chamomile,tea', 'Hot Chocolate' => 'hot,chocolate',
            'Iced Lemon Tea' => 'iced,tea,lemon',
            'Croissant' => 'croissant',
            'Pain au Chocolat' => 'pastry,chocolate',
            'Cinnamon Roll' => 'cinnamon,roll',
            'Blueberry Muffin' => 'blueberry,muffin',
            'Banana Walnut' => 'banana,bread',
            'Glazed Donut' => 'donut,glazed',
            'Tiramisu' => 'tiramisu',
            'Basque Cheesecake' => 'cheesecake',
            'Lava Cake' => 'chocolate,cake',
            'Carrot Cake' => 'carrot,cake',
            'Lemon Tart' => 'lemon,tart',
            'Red Velvet' => 'red,velvet,cake',
            'Avocado Toast' => 'avocado,toast',
            'Eggs Benedict' => 'eggs,benedict',
            'Salmon Bagel' => 'bagel,salmon',
            'Acai Bowl' => 'acai,bowl',
            'Croque Madame' => 'sandwich,grilled',
        ];

        foreach ($map as $needle => $keyword) {
            if (str_contains($name, $needle)) {
                return $keyword;
            }
        }

        return match (true) {
            str_contains($catName, 'Coffee') => 'coffee',
            str_contains($catName, 'Tea') => 'tea',
            str_contains($catName, 'Specialty') => 'latte,art',
            str_contains($catName, 'Pastr') => 'pastry',
            str_contains($catName, 'Cake') => 'cake',
            str_contains($catName, 'Breakfast') => 'breakfast,brunch',
            default => 'cafe',
        };
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
