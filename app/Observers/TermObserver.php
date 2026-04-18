<?php

namespace App\Observers;

use App\Models\Term;
use Illuminate\Support\Facades\Cache;

class TermObserver
{
    public function saved(Term $term): void
    {
        Cache::forget('home.current-term-name');
    }

    public function deleted(Term $term): void
    {
        Cache::forget('home.current-term-name');
    }
}
