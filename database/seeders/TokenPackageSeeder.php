<?php

namespace Database\Seeders;

use App\Models\TokenPackage;
use Illuminate\Database\Seeder;

class TokenPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => '10 Recipes',
                'tokens' => 10,
                'price_cents' => 499,
                'stripe_price_id' => null, // Set after creating in Stripe Dashboard
                'savings_percent' => 0,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => '25 Recipes',
                'tokens' => 25,
                'price_cents' => 999,
                'stripe_price_id' => null,
                'savings_percent' => 20,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '50 Recipes',
                'tokens' => 50,
                'price_cents' => 1499,
                'stripe_price_id' => null,
                'savings_percent' => 40,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            TokenPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }

        $this->command->info('Token packages seeded.');
    }
}
