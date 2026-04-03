<?php

namespace App\Models;

use Database\Factories\InstructorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'employee_id', 'hire_date', 'specializations', 'qualifications', 'bio'])]
class InstructorProfile extends Model
{
    /** @use HasFactory<InstructorProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'specializations' => 'array',
            'qualifications' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique employee ID number.
     */
    public static function generateEmployeeId(): string
    {
        $latest = static::query()
            ->orderByDesc('id')
            ->value('employee_id');

        $number = $latest
            ? ((int) str_replace('INS-', '', $latest)) + 1
            : 1;

        return 'INS-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
