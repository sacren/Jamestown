<?php

use Illuminate\Support\Facades\DB;

test('backfill migration populates slugs for rows that have null slug', function () {
    DB::table('programs')->insert([
        [
            'name' => 'Welding Technology',
            'code' => 'WLD1',
            'slug' => null,
            'duration_weeks' => 12,
            'total_credits_required' => 30,
            'tuition_cost' => 5000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Welding Technology',
            'code' => 'WLD2',
            'slug' => null,
            'duration_weeks' => 12,
            'total_credits_required' => 30,
            'tuition_cost' => 5000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $migration = require database_path('migrations/2026_04_19_183412_backfill_program_slugs.php');
    $migration->up();

    $slugs = DB::table('programs')
        ->orderBy('id')
        ->pluck('slug')
        ->all();

    expect($slugs)->toBe(['welding-technology', 'welding-technology-2']);
});

test('backfill migration leaves existing slugs untouched', function () {
    DB::table('programs')->insert([
        'name' => 'Culinary Arts',
        'code' => 'CUL1',
        'slug' => 'my-custom-slug',
        'duration_weeks' => 12,
        'total_credits_required' => 30,
        'tuition_cost' => 5000,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_04_19_183412_backfill_program_slugs.php');
    $migration->up();

    expect(DB::table('programs')->value('slug'))->toBe('my-custom-slug');
});
