<?php

use App\Enums\EnrollmentStatus;
use App\Models\Announcement;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function currentTerm(): ?Term
    {
        return Term::where('is_active', true)->latest('start_date')->first();
    }

    #[Computed]
    public function isStudent(): bool
    {
        return auth()->user()->hasRole('student');
    }

    #[Computed]
    public function isInstructor(): bool
    {
        return auth()->user()->hasRole('instructor');
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin', 'registrar']);
    }

    // Student widgets
    #[Computed]
    public function studentEnrollments()
    {
        if (! $this->isStudent || ! $this->currentTerm) {
            return collect();
        }

        return Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('status', EnrollmentStatus::Enrolled)
            ->whereHas('section', fn ($q) => $q->where('term_id', $this->currentTerm->id))
            ->with(['section.course', 'section.instructor'])
            ->get();
    }

    #[Computed]
    public function studentRecentGrades()
    {
        if (! $this->isStudent) {
            return collect();
        }

        return Grade::query()
            ->whereHas('enrollment', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['assessment', 'enrollment.section.course'])
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function studentCertificateCount(): int
    {
        if (! $this->isStudent) {
            return 0;
        }

        return Certificate::where('user_id', auth()->id())->whereNull('revoked_at')->count();
    }

    // Instructor widgets
    #[Computed]
    public function instructorSections()
    {
        if (! $this->isInstructor || ! $this->currentTerm) {
            return collect();
        }

        return Section::query()
            ->where('instructor_id', auth()->id())
            ->where('term_id', $this->currentTerm->id)
            ->with('course')
            ->get();
    }

    // Admin widgets
    #[Computed]
    public function totalStudents(): int
    {
        if (! $this->isAdmin) {
            return 0;
        }

        return User::role('student')->count();
    }

    #[Computed]
    public function totalEnrollments(): int
    {
        if (! $this->isAdmin || ! $this->currentTerm) {
            return 0;
        }

        return Enrollment::whereHas('section', fn ($q) => $q->where('term_id', $this->currentTerm->id))->count();
    }

    #[Computed]
    public function totalSections(): int
    {
        if (! $this->isAdmin || ! $this->currentTerm) {
            return 0;
        }

        return Section::where('term_id', $this->currentTerm->id)->where('is_active', true)->count();
    }

    #[Computed]
    public function totalCertificates(): int
    {
        if (! $this->isAdmin) {
            return 0;
        }

        return Certificate::whereNull('revoked_at')->count();
    }

    #[Computed]
    public function recentEnrollments()
    {
        if (! $this->isAdmin) {
            return collect();
        }

        return Enrollment::query()
            ->with(['student', 'section.course'])
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentCertificates()
    {
        if (! $this->isAdmin) {
            return collect();
        }

        return Certificate::query()
            ->with(['student', 'program'])
            ->latest()
            ->take(5)
            ->get();
    }

    // Shared
    #[Computed]
    public function announcements()
    {
        $user = auth()->user();
        $query = Announcement::published()->latest('published_at');

        if ($user->hasRole('student')) {
            $query->forAudience('students');
        } elseif ($user->hasRole('instructor')) {
            $query->forAudience('instructors');
        }

        return $query->take(3)->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:subheading>{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:subheading>
    </div>

    @if (! $this->currentTerm)
        <flux:callout variant="info" icon="information-circle" class="mb-6">
            <flux:callout.text>{{ __('No active term is currently configured.') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Student Dashboard --}}
    @role('student')
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">{{ __('Enrolled Courses') }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $this->studentEnrollments->count() }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">{{ __('Certificates Earned') }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $this->studentCertificateCount }}</div>
            </div>
        </div>

        @if ($this->studentEnrollments->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Current Enrollments') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Instructor') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->studentEnrollments as $enrollment)
                            <flux:table.row :key="$enrollment->id">
                                <flux:table.cell variant="strong">
                                    {{ $enrollment->section->course->code }} — {{ $enrollment->section->course->name }}
                                </flux:table.cell>
                                <flux:table.cell>{{ $enrollment->section->instructor?->name ?? __('TBA') }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        @if ($this->studentRecentGrades->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Recent Grades') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Assessment') }}</flux:table.column>
                        <flux:table.column>{{ __('Score') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->studentRecentGrades as $grade)
                            @php
                                $pct = $grade->assessment->max_points > 0 ? round(((float) $grade->score / (float) $grade->assessment->max_points) * 100, 1) : 0;
                                $color = $pct >= 90 ? 'green' : ($pct >= 70 ? 'amber' : 'red');
                            @endphp
                            <flux:table.row :key="$grade->id">
                                <flux:table.cell variant="strong">{{ $grade->enrollment->section->course->code }}</flux:table.cell>
                                <flux:table.cell>{{ $grade->assessment->title }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$color" inset="top bottom">{{ $pct }}%</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    @endrole

    {{-- Instructor Dashboard --}}
    @role('instructor')
        @if ($this->instructorSections->isNotEmpty())
            <div class="mb-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text variant="subtle" class="text-sm">{{ __('Sections Teaching') }}</flux:text>
                    <div class="mt-1 text-2xl font-bold">{{ $this->instructorSections->count() }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text variant="subtle" class="text-sm">{{ __('Total Students') }}</flux:text>
                    <div class="mt-1 text-2xl font-bold">{{ $this->instructorSections->sum(fn ($s) => $s->currentEnrollmentCount()) }}</div>
                </div>
            </div>

            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('My Sections') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Enrolled') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->instructorSections as $section)
                            <flux:table.row :key="$section->id">
                                <flux:table.cell variant="strong">
                                    {{ $section->course->code }} — {{ $section->course->name }}
                                </flux:table.cell>
                                <flux:table.cell>{{ $section->currentEnrollmentCount() }}/{{ $section->max_enrollment }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    @endrole

    {{-- Admin / Registrar Dashboard --}}
    @role('super-admin|admin|registrar')
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">{{ __('Total Students') }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $this->totalStudents }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">{{ __('Enrollments This Term') }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $this->totalEnrollments }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">{{ __('Active Sections') }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $this->totalSections }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">{{ __('Certificates Issued') }}</flux:text>
                <div class="mt-1 text-2xl font-bold">{{ $this->totalCertificates }}</div>
            </div>
        </div>

        @if ($this->recentEnrollments->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Recent Enrollments') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Student') }}</flux:table.column>
                        <flux:table.column>{{ __('Course') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentEnrollments as $enrollment)
                            <flux:table.row :key="$enrollment->id">
                                <flux:table.cell>{{ $enrollment->student->name }}</flux:table.cell>
                                <flux:table.cell variant="strong">{{ $enrollment->section->course->code }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom">{{ ucfirst($enrollment->status->value) }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif

        @if ($this->recentCertificates->isNotEmpty())
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">{{ __('Recent Certificates') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Student') }}</flux:table.column>
                        <flux:table.column>{{ __('Program') }}</flux:table.column>
                        <flux:table.column>{{ __('Certificate #') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentCertificates as $certificate)
                            <flux:table.row :key="$certificate->id">
                                <flux:table.cell>{{ $certificate->student->name }}</flux:table.cell>
                                <flux:table.cell variant="strong">{{ $certificate->program->name }}</flux:table.cell>
                                <flux:table.cell>{{ $certificate->certificate_number }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    @endrole

    {{-- Announcements (all roles) --}}
    @if ($this->announcements->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-3">{{ __('Announcements') }}</flux:heading>
            <div class="space-y-3">
                @foreach ($this->announcements as $announcement)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between">
                            <div class="font-semibold">{{ $announcement->title }}</div>
                            <flux:text variant="subtle" class="whitespace-nowrap text-xs">{{ $announcement->published_at->diffForHumans() }}</flux:text>
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ Str::limit($announcement->body, 200) }}</p>
                        <flux:text variant="subtle" class="mt-2 text-xs">{{ __('By :name', ['name' => $announcement->author?->name ?? '—']) }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
