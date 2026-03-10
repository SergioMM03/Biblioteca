<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    private function canManageLoans(User $user): bool
    {
        return $user->hasAnyRole(['docente', 'estudiante']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['bibliotecario', 'docente', 'estudiante']);
    }

    public function create(User $user): bool
    {
        return $this->canManageLoans($user);
    }

    public function return(User $user, Loan $loan): bool
    {
        return $this->canManageLoans($user);
    }
}
