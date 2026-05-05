<?php

namespace App\Support\Authorization;

enum RoleName: string
{
    case Admin = 'Admin';
    case User = 'Người dùng';
    case Guest = 'Khách';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
