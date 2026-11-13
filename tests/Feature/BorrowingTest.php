<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_borrow_book(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Borrowing Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Laravel for Beginners',
            'author' => 'Test Author',
            'isbn' => '9780000000010',
            'stock' => 5,
        ]);

        $response = $this->actingAs($member)
            ->postJson('/api/borrowings', [
                'book_id' => $book->id,
                'due_at' => now()->addDays(7)->toDateTimeString(),
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('borrowings', [
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => 'borrowed',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 4,
        ]);
    }

    public function test_member_cannot_borrow_out_of_stock_book(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Out of Stock Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Unavailable Book',
            'author' => 'Test Author',
            'isbn' => '9780000000011',
            'stock' => 0,
        ]);

        $response = $this->actingAs($member)
            ->postJson('/api/borrowings', [
                'book_id' => $book->id,
                'due_at' => now()->addDays(7)->toDateTimeString(),
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Book is out of stock',
            ]);

        $this->assertDatabaseMissing('borrowings', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 0,
        ]);
    }

    public function test_member_can_return_borrowed_book(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Return Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Book to Return',
            'author' => 'Test Author',
            'isbn' => '9780000000012',
            'stock' => 4,
        ]);

        $borrowing = \App\Models\Borrowing::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrower_name' => $member->name,
            'borrowed_at' => now()->subDays(2),
            'due_at' => now()->addDays(5),
            'status' => 'borrowed',
        ]);

        $response = $this->actingAs($member)
            ->putJson("/api/borrowings/{$borrowing->id}/return");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Book returned successfully',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'returned',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 5,
        ]);
    }

    public function test_member_cannot_return_book_twice(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Double Return Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Double Return Book',
            'author' => 'Test Author',
            'isbn' => '9780000000013',
            'stock' => 5,
        ]);

        $borrowing = \App\Models\Borrowing::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrower_name' => $member->name,
            'borrowed_at' => now()->subDays(2),
            'due_at' => now()->addDays(5),
            'status' => 'returned',
            'returned_at' => now(),
        ]);

        $response = $this->actingAs($member)
            ->putJson("/api/borrowings/{$borrowing->id}/return");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'This book has already been returned',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 5,
        ]);
    }

    public function test_member_cannot_return_another_members_borrowing(): void
    {
        $owner = User::factory()->create([
            'role' => 'member',
        ]);

        $otherMember = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Ownership Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Protected Borrowing Book',
            'author' => 'Test Author',
            'isbn' => '9780000000014',
            'stock' => 4,
        ]);

        $borrowing = \App\Models\Borrowing::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'borrower_name' => $owner->name,
            'borrowed_at' => now()->subDays(2),
            'due_at' => now()->addDays(5),
            'status' => 'borrowed',
        ]);

        $response = $this->actingAs($otherMember)
            ->putJson("/api/borrowings/{$borrowing->id}/return");

        $response->assertStatus(404);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 4,
        ]);
    }

    public function test_member_cannot_borrow_book_with_past_due_date(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Due Date Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Due Date Book',
            'author' => 'Test Author',
            'isbn' => '9780000000015',
            'stock' => 5,
        ]);

        $response = $this->actingAs($member)
            ->postJson('/api/borrowings', [
                'book_id' => $book->id,
                'due_at' => now()->subDays(1)->toDateTimeString(),
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('borrowings', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 5,
        ]);
    }

    public function test_member_can_filter_borrowings_by_status(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Filter Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Filter Test Book',
            'author' => 'Test Author',
            'isbn' => '9780000000016',
            'stock' => 5,
        ]);

        // Satu peminjaman aktif
        \App\Models\Borrowing::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrower_name' => $member->name,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7),
            'status' => 'borrowed',
        ]);

        // Satu peminjaman yang sudah dikembalikan
        \App\Models\Borrowing::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrower_name' => $member->name,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
            'returned_at' => now()->subDays(5),
            'status' => 'returned',
        ]);

        $response = $this->actingAs($member)
            ->getJson('/api/borrowings?status=borrowed');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.status', 'borrowed');
    }

    public function test_borrowings_are_paginated(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Pagination Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Pagination Book',
            'author' => 'Test Author',
            'isbn' => '9780000000017',
            'stock' => 20,
        ]);

        for ($i = 0; $i < 12; $i++) {
            \App\Models\Borrowing::create([
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrower_name' => $member->name,
                'borrowed_at' => now()->subDays($i),
                'due_at' => now()->addDays(7),
                'status' => 'borrowed',
            ]);
        }

        $response = $this->actingAs($member)
            ->getJson('/api/borrowings');

        $response->assertStatus(200)
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.total', 12)
            ->assertJsonCount(10, 'data.data');
    }

    public function test_member_can_access_second_page_of_borrowings(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = Category::create([
            'name' => 'Second Page Test',
        ]);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Second Page Book',
            'author' => 'Test Author',
            'isbn' => '9780000000018',
            'stock' => 20,
        ]);

        for ($i = 0; $i < 12; $i++) {
            \App\Models\Borrowing::create([
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrower_name' => $member->name,
                'borrowed_at' => now()->subDays($i),
                'due_at' => now()->addDays(7),
                'status' => 'borrowed',
            ]);
        }

        $response = $this->actingAs($member)
            ->getJson('/api/borrowings?page=2');

        $response->assertStatus(200)
            ->assertJsonPath('data.current_page', 2)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.total', 12)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_guest_cannot_access_borrowing_detail(): void
    {
        $response = $this->getJson('/api/borrowings/1');

        $response->assertStatus(401);
    }
}
