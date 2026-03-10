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

    public function test_user_can_borrow_book(): void
    {
        $book = Book::factory()->create([
            'available_copies' => 3,
            'is_available' => true
        ]);

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/loans', [
            'requester_name' => 'Alumno',
            'book_id' => $book->id
        ])
        ->assertCreated();
    }

    public function test_user_can_return_book(): void
    {
        $book = Book::factory()->create([
            'available_copies' => 3,
            'is_available' => true
        ]);

        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'return_at' => null
        ]);

        $this->postJson('/api/v1/loans/'.$loan->id.'/return')
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function test_loan_history_can_be_viewed(): void
    {
        $student = $this->createUserWithRole('estudiante');
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/loans')
            ->assertOk();
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
