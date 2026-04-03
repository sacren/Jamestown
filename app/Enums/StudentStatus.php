<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Applicant = 'applicant';
    case Active = 'active';
    case Graduated = 'graduated';
    case Withdrawn = 'withdrawn';
    case Suspended = 'suspended';
}
