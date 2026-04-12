<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramAndCourseSeeder extends Seeder
{
    /**
     * Seed programs, courses, and prerequisites.
     */
    public function run(): void
    {
        $programs = $this->seedPrograms();
        $courses = $this->seedCourses($programs);
        $this->seedPrerequisites($courses);
    }

    /**
     * @return array<string, Program>
     */
    private function seedPrograms(): array
    {
        $data = [
            'WLD' => ['name' => 'Welding Technology', 'duration_weeks' => 36, 'total_credits_required' => 45, 'tuition_cost' => 15000.00],
            'HVAC' => ['name' => 'HVAC Technology', 'duration_weeks' => 40, 'total_credits_required' => 48, 'tuition_cost' => 16500.00],
            'ELEC' => ['name' => 'Electrical Technology', 'duration_weeks' => 44, 'total_credits_required' => 52, 'tuition_cost' => 18000.00],
            'PLMB' => ['name' => 'Plumbing Technology', 'duration_weeks' => 32, 'total_credits_required' => 40, 'tuition_cost' => 13500.00],
            'AUTO' => ['name' => 'Automotive Technology', 'duration_weeks' => 48, 'total_credits_required' => 56, 'tuition_cost' => 19500.00],
        ];

        $programs = [];

        foreach ($data as $code => $attrs) {
            $programs[$code] = Program::create([
                'code' => $code,
                'description' => "Comprehensive training program in {$attrs['name']}.",
                ...$attrs,
            ]);
        }

        return $programs;
    }

    /**
     * @param  array<string, Program>  $programs
     * @return array<string, Course>
     */
    private function seedCourses(array $programs): array
    {
        $courseData = [
            // Welding
            'WLD-101' => ['program' => 'WLD', 'name' => 'Introduction to Welding', 'credit_hours' => 3, 'lecture_hours' => 2, 'lab_hours' => 2],
            'WLD-102' => ['program' => 'WLD', 'name' => 'Oxy-Fuel Cutting', 'credit_hours' => 3, 'lecture_hours' => 1, 'lab_hours' => 4],
            'WLD-201' => ['program' => 'WLD', 'name' => 'MIG Welding', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'WLD-202' => ['program' => 'WLD', 'name' => 'TIG Welding', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'WLD-301' => ['program' => 'WLD', 'name' => 'Pipe Welding', 'credit_hours' => 4, 'lecture_hours' => 1, 'lab_hours' => 6],

            // HVAC
            'HVAC-101' => ['program' => 'HVAC', 'name' => 'HVAC Fundamentals', 'credit_hours' => 3, 'lecture_hours' => 2, 'lab_hours' => 2],
            'HVAC-102' => ['program' => 'HVAC', 'name' => 'Refrigeration Principles', 'credit_hours' => 3, 'lecture_hours' => 2, 'lab_hours' => 2],
            'HVAC-201' => ['program' => 'HVAC', 'name' => 'Electrical for HVAC', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'HVAC-202' => ['program' => 'HVAC', 'name' => 'System Design', 'credit_hours' => 4, 'lecture_hours' => 3, 'lab_hours' => 2],
            'HVAC-301' => ['program' => 'HVAC', 'name' => 'Commercial HVAC Systems', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],

            // Electrical
            'ELEC-101' => ['program' => 'ELEC', 'name' => 'Electrical Theory', 'credit_hours' => 3, 'lecture_hours' => 3, 'lab_hours' => 0],
            'ELEC-102' => ['program' => 'ELEC', 'name' => 'Residential Wiring', 'credit_hours' => 3, 'lecture_hours' => 1, 'lab_hours' => 4],
            'ELEC-201' => ['program' => 'ELEC', 'name' => 'Commercial Wiring', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'ELEC-202' => ['program' => 'ELEC', 'name' => 'Motor Controls', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'ELEC-301' => ['program' => 'ELEC', 'name' => 'PLC Programming', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'ELEC-302' => ['program' => 'ELEC', 'name' => 'National Electric Code', 'credit_hours' => 3, 'lecture_hours' => 3, 'lab_hours' => 0],

            // Plumbing
            'PLMB-101' => ['program' => 'PLMB', 'name' => 'Plumbing Fundamentals', 'credit_hours' => 3, 'lecture_hours' => 2, 'lab_hours' => 2],
            'PLMB-102' => ['program' => 'PLMB', 'name' => 'Pipe Fitting', 'credit_hours' => 3, 'lecture_hours' => 1, 'lab_hours' => 4],
            'PLMB-201' => ['program' => 'PLMB', 'name' => 'Drainage Systems', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'PLMB-202' => ['program' => 'PLMB', 'name' => 'Water Supply Systems', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],

            // Automotive
            'AUTO-101' => ['program' => 'AUTO', 'name' => 'Automotive Fundamentals', 'credit_hours' => 3, 'lecture_hours' => 2, 'lab_hours' => 2],
            'AUTO-102' => ['program' => 'AUTO', 'name' => 'Engine Systems', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'AUTO-201' => ['program' => 'AUTO', 'name' => 'Brake Systems', 'credit_hours' => 3, 'lecture_hours' => 1, 'lab_hours' => 4],
            'AUTO-202' => ['program' => 'AUTO', 'name' => 'Electrical Systems', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'AUTO-301' => ['program' => 'AUTO', 'name' => 'Transmission and Drivetrain', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
            'AUTO-302' => ['program' => 'AUTO', 'name' => 'Engine Performance', 'credit_hours' => 4, 'lecture_hours' => 2, 'lab_hours' => 4],
        ];

        $courses = [];

        $tuitionOptions = [450, 500, 600, 750, 900];

        foreach ($courseData as $code => $attrs) {
            $programCode = $attrs['program'];
            unset($attrs['program']);

            $courses[$code] = Course::create([
                'program_id' => $programs[$programCode]->id,
                'code' => $code,
                'description' => "Course covering {$attrs['name']}.",
                'tuition_amount' => $tuitionOptions[array_rand($tuitionOptions)],
                ...$attrs,
            ]);
        }

        return $courses;
    }

    /**
     * @param  array<string, Course>  $courses
     */
    private function seedPrerequisites(array $courses): void
    {
        $prerequisites = [
            // Welding
            'WLD-201' => ['WLD-101'],
            'WLD-202' => ['WLD-101'],
            'WLD-301' => ['WLD-201', 'WLD-202'],

            // HVAC
            'HVAC-201' => ['HVAC-101'],
            'HVAC-202' => ['HVAC-101'],
            'HVAC-301' => ['HVAC-201'],

            // Electrical
            'ELEC-201' => ['ELEC-102'],
            'ELEC-202' => ['ELEC-101'],
            'ELEC-301' => ['ELEC-201'],

            // Plumbing
            'PLMB-201' => ['PLMB-101'],
            'PLMB-202' => ['PLMB-101'],

            // Automotive
            'AUTO-201' => ['AUTO-101'],
            'AUTO-202' => ['AUTO-101'],
            'AUTO-301' => ['AUTO-201'],
            'AUTO-302' => ['AUTO-102'],
        ];

        foreach ($prerequisites as $courseCode => $prereqCodes) {
            $course = $courses[$courseCode];
            $prereqIds = array_map(fn ($code) => $courses[$code]->id, $prereqCodes);
            $course->prerequisites()->attach($prereqIds);
        }
    }
}
