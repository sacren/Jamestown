<?php

namespace App\Observers;

use App\Models\Program;
use Illuminate\Support\Facades\Cache;

class ProgramObserver
{
    public function saved(Program $program): void
    {
        $this->forgetHomeCache();
    }

    public function deleted(Program $program): void
    {
        $this->forgetHomeCache();
    }

    private function forgetHomeCache(): void
    {
        Cache::forget('home.program-count');
        Cache::forget('home.featured-programs');
    }
}
