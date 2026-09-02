<?php

namespace App\Actions\Users;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UpdateUser
{
    use ProfileValidationRules;

    /**
     * Update the user's profile attributes.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): User
    {
        Validator::make($input, [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($user->getKey()),
        ])->validate();

        $user->fill([
            'name' => $input['name'],
            'email' => $input['email'],
        ])->save();

        return $user;
    }
}
