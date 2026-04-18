<?php

namespace App\Observers;

use App\Models\Course;
use Illuminate\Support\Facades\Cache;

class CourseObserver
{
    public function saved(Course $course): void
    {
        $this->forgetHomeCache();
    }

    public function deleted(Course $course): void
    {
        $this->forgetHomeCache();
    }

    private function forgetHomeCache(): void
    {
        Cache::forget('home.course-count');
    }
}
