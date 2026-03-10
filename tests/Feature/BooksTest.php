<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BooksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_books_can_be_listed(): void
    {
        Book::factory()->create();

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/books')
            ->assertOk();
    }

    public function test_book_detail_can_be_viewed(): void
    {
        $book = Book::factory()->create();

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('id', $book->id);
    }

    public function test_librarian_can_create_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertCreated();
    }

    public function test_librarian_can_update_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $book = Book::factory()->create();

        $this->putJson('/api/v1/books/'.$book->id, [
            'title' => 'Nuevo título'
        ])
        ->assertOk()
        ->assertJsonPath('title', 'Nuevo título');
    }

    public function test_librarian_can_delete_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/'.$book->id)
            ->assertNoContent();
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function bookPayload(): array
    {
        return [
            'title' => 'DDD en PHP '.uniqid(),
            'description' => 'Libro de arquitectura de software',
            'ISBN' => '978'.rand(1000000000,9999999999),
            'total_copies' => 5,
            'available_copies' => 5
        ];
    }
}
