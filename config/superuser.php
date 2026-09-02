<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Superuser Credentials
    |--------------------------------------------------------------------------
    |
    | The env-driven account automatically created by the SuperuserSeeder.
    | When the email is empty the seeder skips creating the account.
    |
    */

    'email' => env('SUPERUSER_EMAIL'),

    'name' => env('SUPERUSER_NAME', 'Satu ID Superuser'),

    'password' => env('SUPERUSER_PASSWORD'),

];
