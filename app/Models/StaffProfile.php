<?php

namespace App\Models;

use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'employee_id', 'department', 'title'])]
class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory;

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
            ? ((int) str_replace('STF-', '', $latest)) + 1
            : 1;

        return 'STF-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
