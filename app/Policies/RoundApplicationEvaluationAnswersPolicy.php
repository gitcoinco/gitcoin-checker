<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RoundApplicationEvaluationAnswer; // Assuming this is your model
use App\Models\RoundApplicationEvaluationAnswers;

class RoundApplicationEvaluationAnswersPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the given evaluation answer can be deleted by the user.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RoundApplicationEvaluationAnswer  $answer
     * @return bool
     */
    public function delete(User $user, RoundApplicationEvaluationAnswers $answer)
    {
        return $answer->user_id == $user->id;
    }
}
