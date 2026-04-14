<?php

namespace Database\Seeders;

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentStatusChanged;
use App\Notifications\NewAnnouncement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $registrar = User::where('email', 'registrar@example.com')->first();

        if (! $admin) {
            return;
        }

        $welcome = Announcement::create([
            'title' => 'Welcome to Fall 2026',
            'body' => 'Welcome to the Fall 2026 semester at Empire Trade School! We are excited to have you join us. Please check your schedule and make sure you are registered for all your courses. If you have any questions, contact the registrar office.',
            'audience' => AnnouncementAudience::All,
            'published_at' => now()->subDays(5),
            'notified_at' => now()->subDays(5),
            'author_id' => $admin->id,
        ]);

        $registration = Announcement::create([
            'title' => 'Registration Deadline Reminder',
            'body' => 'This is a reminder that the registration deadline for Fall 2026 is approaching. Please make sure to complete your enrollment before the deadline to avoid late fees.',
            'audience' => AnnouncementAudience::Students,
            'published_at' => now()->subDays(2),
            'notified_at' => now()->subDays(2),
            'author_id' => $registrar?->id ?? $admin->id,
        ]);

        $gradeDeadline = Announcement::create([
            'title' => 'Grade Submission Deadline',
            'body' => 'All instructors must submit final grades by the end of the semester. Please ensure all assessments are graded and entered into the system before the deadline.',
            'audience' => AnnouncementAudience::Instructors,
            'published_at' => now()->subDay(),
            'notified_at' => now()->subDay(),
            'author_id' => $admin->id,
        ]);

        Announcement::create([
            'title' => 'Spring 2027 Preview',
            'body' => 'We are already planning for Spring 2027. New programs and courses will be announced soon. Stay tuned for more information!',
            'audience' => AnnouncementAudience::All,
            'published_at' => null,
            'author_id' => $admin->id,
        ]);

        $this->seedDemoNotifications($welcome, $registration);
    }

    private function seedDemoNotifications(Announcement $welcome, Announcement $registration): void
    {
        $student = User::where('email', 'student@example.com')->first();
        if (! $student) {
            return;
        }

        // Announcement notification (read)
        $student->notify(new NewAnnouncement($welcome));
        $student->notifications()->latest()->first()?->markAsRead();

        // Announcement notification (unread)
        $student->notify(new NewAnnouncement($registration));

        // Enrollment notification if student has enrollments
        $enrollment = Enrollment::where('user_id', $student->id)->with('section.course')->first();
        if ($enrollment) {
            $student->notify(new EnrollmentStatusChanged($enrollment, $enrollment->status));
        }
    }
}
