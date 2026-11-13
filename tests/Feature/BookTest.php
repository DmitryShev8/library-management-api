<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_isbn_must_be_unique(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Programming',
        ]);

        Book::create([
            'category_id' => $category->id,
            'title' => 'Existing Book',
            'author' => 'Test Author',
            'isbn' => '9780000000001',
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/books', [
                'category_id' => $category->id,
                'title' => 'Duplicate Book',
                'author' => 'Another Author',
                'isbn' => '9780000000001',
                'stock' => 10,
            ]);

        $response->assertStatus(422);
    }

    public function test_book_stock_cannot_be_negative(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Science',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/books', [
                'category_id' => $category->id,
                'title' => 'Physics Fundamentals',
                'author' => 'Test Author',
                'isbn' => '9780000000006',
                'stock' => -5,
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9780000000006',
        ]);
    }

    public function test_book_category_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/books', [
                'category_id' => 99999,
                'title' => 'Unknown Category Book',
                'author' => 'Test Author',
                'isbn' => '9780000000007',
                'stock' => 5,
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9780000000007',
        ]);
    }

    public function test_authenticated_user_can_update_book(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Database',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Learning SQL',
            'author' => 'Alan Beaulieu',
            'isbn' => '9780000000008',
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/books/{$book->id}", [
                'title' => 'Learning Advanced SQL',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Learning Advanced SQL',
        ]);
    }
}