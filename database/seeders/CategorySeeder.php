<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Medical',
                'total_items' => 8,
                'icon_url' => null, // Will be uploaded via API
            ],
            [
                'name' => 'School',
                'total_items' => 12,
                'icon_url' => null, // Will be uploaded via API
            ],
            [
                'name' => 'Food',
                'total_items' => 4,
                'icon_url' => null, // Will be uploaded via API
            ],
            [
                'name' => 'Clothing',
                'total_items' => 15,
                'icon_url' => null,
            ],
            [
                'name' => 'Electronics',
                'total_items' => 6,
                'icon_url' => null,
            ],
            [
                'name' => 'Books',
                'total_items' => 20,
                'icon_url' => null,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}
