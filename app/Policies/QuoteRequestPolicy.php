<?php

namespace App\Policies;

use App\Models\QuoteRequest;
use App\Models\User;

class QuoteRequestPolicy
{
    /**
     * Viewing a quote request is admin-only, but the referencing customer may
     * also see the "new" status variety in their history if ever needed.
     */
    public function view(User $user, QuoteRequest $quote): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, QuoteRequest $quote): bool
    {
        return $user->isAdmin();
    }
}
