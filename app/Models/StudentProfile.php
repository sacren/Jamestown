<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'student_id_number', 'enrollment_date', 'status'])]
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'enrollment_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique student ID number.
     */
    public static function generateIdNumber(): string
    {
        $latest = static::query()
            ->orderByDesc('id')
            ->value('student_id_number');

        $number = $latest
            ? ((int) str_replace('STU-', '', $latest)) + 1
            : 1;

        return 'STU-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
