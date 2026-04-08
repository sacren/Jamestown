<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'section_schedule_id', 'date', 'status', 'notes', 'recorded_by'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'date' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function sectionSchedule(): BelongsTo
    {
        return $this->belongsTo(SectionSchedule::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
