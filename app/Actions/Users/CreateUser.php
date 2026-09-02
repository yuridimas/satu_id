<?php

namespace App\Actions\Users;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateUser
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a new user account.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => $this->nameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = new User;

        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->password = $input['password'];
        $user->role = UserRole::User;
        $user->active = true;

        $user->save();

        return $user;
    }
}
