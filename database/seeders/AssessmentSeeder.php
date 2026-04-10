<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Section;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Seed a standard set of assessments for each section that has enrollments.
     */
    public function run(): void
    {
        $sections = Section::query()
            ->has('enrollments')
            ->get();

        $template = [
            ['title' => 'Midterm Exam', 'type' => AssessmentType::Exam, 'max_points' => 100, 'sort_order' => 1],
            ['title' => 'Lab Exercise 1', 'type' => AssessmentType::Lab, 'max_points' => 30, 'sort_order' => 2],
            ['title' => 'Lab Exercise 2', 'type' => AssessmentType::Lab, 'max_points' => 30, 'sort_order' => 3],
            ['title' => 'Course Project', 'type' => AssessmentType::Project, 'max_points' => 50, 'sort_order' => 4],
            ['title' => 'Final Exam', 'type' => AssessmentType::Final, 'max_points' => 200, 'sort_order' => 5],
        ];

        foreach ($sections as $section) {
            foreach ($template as $row) {
                Assessment::query()->updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'title' => $row['title'],
                    ],
                    [
                        'type' => $row['type'],
                        'max_points' => $row['max_points'],
                        'sort_order' => $row['sort_order'],
                    ],
                );
            }
        }
    }
}
