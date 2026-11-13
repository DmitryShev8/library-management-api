<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_create_category(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $response = $this->actingAs($member)
            ->postJson('/api/categories', [
                'name' => 'Technology',
                'description' => 'Technology books',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('categories', [
            'name' => 'Technology',
        ]);
    }

    public function test_staff_can_create_book(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Technology',
            'description' => 'Technology books',
        ]);

        $response = $this->actingAs($staff)
            ->postJson('/api/books', [
                'category_id' => $category->id,
                'title' => 'Clean Architecture',
                'author' => 'Robert C. Martin',
                'isbn' => '9780134494166',
                'stock' => 5,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'title' => 'Clean Architecture',
            'isbn' => '9780134494166',
        ]);
    }

    public function test_admin_can_delete_book(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Testing',
            'description' => 'Testing category',
        ]);

        $book = \App\Models\Book::create([
            'category_id' => $category->id,
            'title' => 'Book to Delete',
            'author' => 'Test Author',
            'isbn' => '9780000000001',
            'stock' => 5,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_member_cannot_delete_book(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Member Test',
            'description' => 'Testing category',
        ]);

        $book = \App\Models\Book::create([
            'category_id' => $category->id,
            'title' => 'Protected Book',
            'author' => 'Test Author',
            'isbn' => '9780000000002',
            'stock' => 5,
        ]);

        $response = $this->actingAs($member)
            ->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Protected Book',
        ]);
    }
    public function test_member_cannot_view_another_members_borrowing(): void
    {
        $memberA = User::factory()->create([
            'role' => 'member',
        ]);

        $memberB = User::factory()->create([
            'role' => 'member',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Ownership Test',
            'description' => 'Testing ownership',
        ]);

        $book = \App\Models\Book::create([
            'category_id' => $category->id,
            'title' => 'Ownership Book',
            'author' => 'Test Author',
            'isbn' => '9780000000003',
            'stock' => 5,
        ]);

        $borrowing = \App\Models\Borrowing::create([
            'user_id' => $memberA->id,
            'book_id' => $book->id,
            'borrower_name' => $memberA->name,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7),
            'status' => 'borrowed',
        ]);

        $response = $this->actingAs($memberB)
            ->getJson("/api/borrowings/{$borrowing->id}");

        $response->assertStatus(404);
    }

    public function test_member_can_only_view_own_borrowings(): void
    {
        $memberA = User::factory()->create([
            'role' => 'member',
        ]);

        $memberB = User::factory()->create([
            'role' => 'member',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'List Ownership Test',
        ]);

        $book = \App\Models\Book::create([
            'category_id' => $category->id,
            'title' => 'Borrowing List Book',
            'author' => 'Test Author',
            'isbn' => '9780000000004',
            'stock' => 5,
        ]);

        \App\Models\Borrowing::create([
            'user_id' => $memberA->id,
            'book_id' => $book->id,
            'borrower_name' => $memberA->name,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7),
            'status' => 'borrowed',
        ]);

        $response = $this->actingAs($memberB)
            ->getJson('/api/borrowings');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 0);
    }
    public function test_staff_cannot_borrow_book(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($staff)
            ->postJson('/api/borrowings', []);

        $response->assertStatus(403);
    }

    public function test_staff_cannot_return_borrowing(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $member = User::factory()->create([
            'role' => 'member',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Return Test',
        ]);

        $book = \App\Models\Book::create([
            'category_id' => $category->id,
            'title' => 'Return Test Book',
            'author' => 'Test Author',
            'isbn' => '9780000000005',
            'stock' => 4,
        ]);

        $borrowing = \App\Models\Borrowing::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrower_name' => $member->name,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7),
            'status' => 'borrowed',
        ]);

        $response = $this->actingAs($staff)
            ->putJson("/api/borrowings/{$borrowing->id}/return");

        $response->assertStatus(403);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
        ]);
    }
    public function test_guest_cannot_access_borrowings(): void
    {
        $response = $this->getJson('/api/borrowings');

        $response->assertStatus(401);
    }
}
