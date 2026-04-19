<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $used = DB::table('programs')
            ->whereNotNull('slug')
            ->pluck('slug')
            ->all();

        DB::table('programs')
            ->whereNull('slug')
            ->orderBy('id')
            ->select(['id', 'name'])
            ->each(function (object $program) use (&$used): void {
                $base = Str::slug($program->name);
                $slug = $base;
                $suffix = 2;

                while (in_array($slug, $used, true)) {
                    $slug = $base.'-'.$suffix++;
                }

                DB::table('programs')
                    ->where('id', $program->id)
                    ->update(['slug' => $slug]);

                $used[] = $slug;
            });
    }

    public function down(): void
    {
        DB::table('programs')->update(['slug' => null]);
    }
};
