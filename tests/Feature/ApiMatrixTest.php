<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // AUTH

    public function test_auth_login_logout_and_profile_flow(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('test1234'),
        ]);
        $user->assignRole('estudiante');

        $login = $this->postJson('/api/v1/login', [
            'email'    => $user->email,
            'password' => 'test1234',
        ]);

        $login->assertOk()->assertJsonStructure([
            'access_token',
            'token_type',
            'user',
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    // BOOKS — listado y detalle

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
        $book    = Book::factory()->create();
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/books/' . $book->id)
            ->assertOk()
            ->assertJsonPath('id', $book->id);
    }

    // BOOKS — solo bibliotecario puede crear

    public function test_librarian_can_create_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertCreated();
    }

    public function test_teacher_cannot_create_book(): void
    {
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_student_cannot_create_book(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/books', $this->bookPayload())
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    // BOOKS — solo bibliotecario puede editar

    public function test_librarian_can_update_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $book = Book::factory()->create();

        $this->putJson('/api/v1/books/' . $book->id, ['title' => 'Nuevo título'])
            ->assertOk()
            ->assertJsonPath('title', 'Nuevo título');
    }

    public function test_teacher_cannot_update_book(): void
    {
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $book = Book::factory()->create();

        $this->putJson('/api/v1/books/' . $book->id, ['title' => 'No permitido'])
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_student_cannot_update_book(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $book = Book::factory()->create();

        $this->putJson('/api/v1/books/' . $book->id, ['title' => 'No permitido'])
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    // BOOKS — solo bibliotecario puede eliminar

    public function test_librarian_can_delete_book(): void
    {
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/' . $book->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_teacher_cannot_delete_book(): void
    {
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/' . $book->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_student_cannot_delete_book(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $book = Book::factory()->create();

        $this->deleteJson('/api/v1/books/' . $book->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    // LOANS — historial, préstamo y devolución

    public function test_authenticated_users_can_view_loans_history(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $book = Book::factory()->create();
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/v1/loans')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $loan->id,
                'requester_name' => $loan->requester_name,
            ]);
    }

    public function test_teacher_can_borrow_and_return_book(): void
    {
        $book    = Book::factory()->create([
            'total_copies'     => 3,
            'available_copies' => 3,
            'is_available'     => true,
        ]);
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $loan = $this->postJson('/api/v1/loans', [
            'requester_name' => 'Profesor Uno',
            'book_id'        => $book->id,
        ])->assertCreated();

        $loanId = $loan->json('id');

        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'available_copies' => 2,
        ]);

        $this->postJson('/api/v1/loans/' . $loanId . '/return')
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'available_copies' => 3,
        ]);
    }

    public function test_student_can_borrow_and_return_book(): void
    {
        $book    = Book::factory()->create([
            'total_copies'     => 2,
            'available_copies' => 2,
            'is_available'     => true,
        ]);
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $loan = $this->postJson('/api/v1/loans', [
            'requester_name' => 'Alumno Uno',
            'book_id'        => $book->id,
        ])->assertCreated();

        $this->postJson('/api/v1/loans/' . $loan->json('id') . '/return')
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function test_cannot_return_already_returned_loan(): void
    {
        $book    = Book::factory()->create([
            'total_copies'     => 3,
            'available_copies' => 3,
            'is_available'     => true,
        ]);
        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $loan   = $this->postJson('/api/v1/loans', [
            'requester_name' => 'Profesor Dos',
            'book_id'        => $book->id,
        ])->assertCreated();

        $loanId = $loan->json('id');

        $this->postJson('/api/v1/loans/' . $loanId . '/return')->assertOk();

        $this->postJson('/api/v1/loans/' . $loanId . '/return')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Loan already returned');
    }

    public function test_librarian_cannot_borrow_book(): void
    {
        $book      = Book::factory()->create(['is_available' => true, 'available_copies' => 3]);
        $librarian = $this->createUserWithRole('bibliotecario');
        Sanctum::actingAs($librarian);

        $this->postJson('/api/v1/loans', [
            'requester_name' => 'Bibliotecario',
            'book_id'        => $book->id,
        ])->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_librarian_cannot_return_loan(): void
    {
        $book      = Book::factory()->create(['available_copies' => 3]);
        $librarian = $this->createUserWithRole('bibliotecario');

        $activeLoan = Loan::factory()->create([
            'requester_name' => 'Alumno 1',
            'book_id'        => $book->id,
            'return_at'      => null,
        ]);

        Sanctum::actingAs($librarian);

        $this->postJson('/api/v1/loans/' . $activeLoan->id . '/return')
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    // LOANS — libro no disponible

    public function test_loan_fails_when_book_is_not_available(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $book = Book::factory()->create([
            'total_copies'     => 1,
            'available_copies' => 0,
            'is_available'     => false,
        ]);

        $this->postJson('/api/v1/loans', [
            'requester_name' => 'Alumno 2',
            'book_id'        => $book->id,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Book is not available');
    }

    // Helpers

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function bookPayload(): array
    {
        return [
            'title'             => 'DDD en PHP ' . uniqid(),
            'description'       => 'Libro de arquitectura de software',
            'ISBN'              => '978' . rand(1000000000, 9999999999),
            'total_copies'      => 5,
            'available_copies'  => 5,
        ];
    }
}
