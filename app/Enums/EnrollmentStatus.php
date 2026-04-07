<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Dropped = 'dropped';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';
}
