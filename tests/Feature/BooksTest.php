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

    public function test_books_are_listed_by_authenticated_users(): void
    {
        Book::factory()->create();

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/books')
            ->assertOk();
    }

    public function test_book_detail_is_viewed_by_authenticated_users(): void
    {
        $book = Book::factory()->create();

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/books/' . $book->id)
            ->assertOk()
            ->assertJsonPath('id', $book->id);
    }

    public function test_books_list_requires_authentication(): void
    {
        $this->getJson('/api/v1/books')
            ->assertUnauthorized();
    }

    public function test_book_detail_returns_404_if_not_found(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/books/999')
            ->assertNotFound();
    }

    public function test_librarian_can_create_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertCreated();
    }

    public function test_create_book_fails_with_invalid_data(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->postJson('/api/v1/books', [
            'title' => '',
            'ISBN' => '',
            'total_copies' => null,
        ])
        ->assertStatus(422);
    }

    public function test_teacher_cannot_create_book(): void
    {
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertForbidden();
    }

    public function test_student_cannot_create_book(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertForbidden();
    }

    public function test_librarian_can_update_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $book = Book::factory()->create();

        $this->putJson('/api/v1/books/' . $book->id, [
            'title' => 'Nuevo título'
        ])
        ->assertOk();
    }

    public function test_update_book_returns_404_if_not_found(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->putJson('/api/v1/books/999', [
            'title' => 'Libro inexistente'
        ])
        ->assertNotFound(422);
    }

    public function test_librarian_can_delete_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/' . $book->id)
            ->assertNoContent();
    }

    public function test_delete_book_returns_404_if_not_found(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->deleteJson('/api/v1/books/999')
            ->assertNotFound();
    }

    public function test_teacher_cannot_delete_book(): void
    {
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/' . $book->id)
            ->assertForbidden();
    }

    public function test_student_cannot_delete_book(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/' . $book->id)
            ->assertForbidden();
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
            'title' => 'DDD en PHP ' . uniqid(),
            'description' => 'Libro de arquitectura de software',
            'ISBN' => '978' . rand(1000000000, 9999999999),
            'total_copies' => 5,
            'available_copies' => 5,
        ];
    }
}
