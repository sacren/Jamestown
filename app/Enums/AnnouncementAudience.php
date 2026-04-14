<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Students = 'students';
    case Instructors = 'instructors';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Everyone',
            self::Students => 'Students',
            self::Instructors => 'Instructors',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::All => 'blue',
            self::Students => 'green',
            self::Instructors => 'amber',
        };
    }
}
