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
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

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

        $response = $this->actingAs($staff)
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
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $category = Category::create([
            'name' => 'Science',
        ]);

        $response = $this->actingAs($staff)
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
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($staff)
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

    public function test_staff_can_update_book(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

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

        $response = $this->actingAs($staff)
            ->putJson("/api/books/{$book->id}", [
                'title' => 'Learning Advanced SQL',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Learning Advanced SQL',
        ]);
    }

    public function test_staff_cannot_delete_book(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $category = Category::create([
            'name' => 'Authorization Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Protected Book',
            'author' => 'Test Author',
            'isbn' => '9780000000009',
            'stock' => 5,
        ]);

        $response = $this->actingAs($staff)
            ->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
