<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Programming',
                'description' => 'Books about programming and software development',
            ],
            [
                'name' => 'Database',
                'description' => 'Books about database management and SQL',
            ],
            [
                'name' => 'History',
                'description' => 'Books about historical events and civilizations',
            ],
            [
                'name' => 'Science',
                'description' => 'Books about science and technology',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}