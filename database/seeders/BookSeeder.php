<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Category;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'category' => 'Programming',
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9780132350884',
                'stock' => 5,
            ],
            [
                'category' => 'Programming',
                'title' => 'The Pragmatic Programmer',
                'author' => 'David Thomas',
                'isbn' => '9780135957059',
                'stock' => 3,
            ],
            [
                'category' => 'Programming',
                'title' => 'Design Patterns',
                'author' => 'Erich Gamma',
                'isbn' => '9780201633610',
                'stock' => 4,
            ],
            [
                'category' => 'Database',
                'title' => 'Database System Concepts',
                'author' => 'Abraham Silberschatz',
                'isbn' => '9780078022159',
                'stock' => 4,
            ],
            [
                'category' => 'Database',
                'title' => 'Learning SQL',
                'author' => 'Alan Beaulieu',
                'isbn' => '9781492057611',
                'stock' => 6,
            ],
            [
                'category' => 'History',
                'title' => 'Sapiens',
                'author' => 'Yuval Noah Harari',
                'isbn' => '9780062316097',
                'stock' => 2,
            ],
            [
                'category' => 'History',
                'title' => 'Guns, Germs, and Steel',
                'author' => 'Jared Diamond',
                'isbn' => '9780393354324',
                'stock' => 3,
            ],
            [
                'category' => 'Science',
                'title' => 'A Brief History of Time',
                'author' => 'Stephen Hawking',
                'isbn' => '9780553380163',
                'stock' => 5,
            ],
            [
                'category' => 'Science',
                'title' => 'The Selfish Gene',
                'author' => 'Richard Dawkins',
                'isbn' => '9780198788607',
                'stock' => 4,
            ],
        ];

        foreach ($books as $book) {
            $category = Category::where('name', $book['category'])
                ->firstOrFail();

            Book::updateOrCreate(
                ['isbn' => $book['isbn']],
                [
                    'category_id' => $category->id,
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'stock' => $book['stock'],
                ]
            );
        }
    }
}