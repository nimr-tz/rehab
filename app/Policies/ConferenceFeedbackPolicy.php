<?php

namespace App\Policies;

use App\Models\ConferenceFeedback;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConferenceFeedbackPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ConferenceFeedback $conferenceFeedback): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Everyone can submit feedback
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ConferenceFeedback $conferenceFeedback): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConferenceFeedback $conferenceFeedback): bool
    {
        return $user->hasRole('admin');
    }
}
