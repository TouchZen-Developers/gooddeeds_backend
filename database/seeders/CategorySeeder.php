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
                'icon_url' => null, // Will be uploaded via API
            ],
            [
                'name' => 'School',
                'icon_url' => null, // Will be uploaded via API
            ],
            [
                'name' => 'Food',
                'icon_url' => null, // Will be uploaded via API
            ],
            [
                'name' => 'Clothing',
                'icon_url' => null,
            ],
            [
                'name' => 'Electronics',
                'icon_url' => null,
            ],
            [
                'name' => 'Books',
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
