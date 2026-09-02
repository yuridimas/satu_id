<?php

namespace App\Enums;

enum UserRole: string
{
    case Superuser = 'superuser';
    case User = 'user';

    /**
     * Determine whether the role is the superuser role.
     */
    public function isSuperuser(): bool
    {
        return $this === self::Superuser;
    }
}
