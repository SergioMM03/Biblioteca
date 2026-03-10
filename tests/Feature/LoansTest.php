<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

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
            ]);
    }

    public function test_loans_history_requires_authentication(): void
    {
        $this->getJson('/api/v1/loans')
            ->assertUnauthorized();
    }

    public function test_teacher_can_borrow_and_return_book(): void
    {
        $book = Book::factory()->create([
            'total_copies' => 3,
            'available_copies' => 3,
            'is_available' => true,
        ]);

        $teacher = $this->createUserWithRole('docente');
        Sanctum::actingAs($teacher);

        $loan = $this->postJson('/api/v1/loans', [
            'requester_name' => 'Profesor Uno',
            'book_id' => $book->id,
        ])
        ->assertCreated();

        $loanId = $loan->json('id');

        $this->postJson('/api/v1/loans/' . $loanId . '/return')
            ->assertOk();
    }

    public function test_student_can_borrow_and_return_book(): void
    {
        $book = Book::factory()->create([
            'total_copies' => 2,
            'available_copies' => 2,
            'is_available' => true,
        ]);

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $loan = $this->postJson('/api/v1/loans', [
            'requester_name' => 'Alumno Uno',
            'book_id' => $book->id,
        ])
        ->assertCreated();

        $this->postJson('/api/v1/loans/' . $loan->json('id') . '/return')
            ->assertOk();
    }

    public function test_loan_fails_when_book_does_not_exist(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/loans', [
            'requester_name' => 'Alumno',
            'book_id' => 999,
        ])
        ->assertNotFound(422);
    }

    public function test_return_fails_when_loan_does_not_exist(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/loans/999/return')
            ->assertNotFound(422);
    }

    public function test_loan_validation_fails_with_missing_fields(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/loans', [])
            ->assertStatus(422);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }
}
