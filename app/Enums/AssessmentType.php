<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Assignment = 'assignment';
    case Exam = 'exam';
    case Quiz = 'quiz';
    case Project = 'project';
    case Lab = 'lab';
    case Presentation = 'presentation';
    case Final = 'final';
}
