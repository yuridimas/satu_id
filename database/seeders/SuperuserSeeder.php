<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SuperuserSeeder extends Seeder
{
    /**
     * Seed the initial superuser account from environment variables.
     */
    public function run(): void
    {
        $email = config('superuser.email');
        $password = config('superuser.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command->warn('SUPERUSER_EMAIL/SUPERUSER_PASSWORD not set; skipping superuser seeder.');

            return;
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        $user->name = config('superuser.name');
        $user->email = $email;
        $user->email_verified_at ??= Carbon::now();
        $user->password = $password;
        $user->role = UserRole::Superuser;
        $user->active = true;
        $user->deleted_at = null;

        $user->save();
    }
}
