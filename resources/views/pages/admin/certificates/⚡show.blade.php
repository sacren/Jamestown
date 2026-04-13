<?php

use App\Enums\EnrollmentStatus;
use App\Models\Certificate;
use App\Models\Enrollment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Certificate Details')] class extends Component {
    public Certificate $certificate;
    public string $revokeNotes = '';

    public function mount(Certificate $certificate): void
    {
        abort_unless(auth()->user()->can('certificates.view-any'), 403);
        $this->certificate = $certificate->load(['student.studentProfile', 'program', 'issuer', 'revoker']);
    }

    public function revokeCertificate(): void
    {
        abort_unless(auth()->user()->can('certificates.manage'), 403);

        if ($this->certificate->isRevoked()) {
            return;
        }

        $this->certificate->update([
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
            'notes' => $this->revokeNotes ?: null,
        ]);

        $this->certificate->refresh();
    }

    #[Computed]
    public function completedCourses()
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $this->certificate->user_id)
            ->where('status', EnrollmentStatus::Completed)
            ->whereHas('section.course', fn ($q) => $q->where('program_id', $this->certificate->program_id))
            ->with(['section.course', 'section.term', 'grades.assessment'])
            ->get()
            ->unique(fn ($e) => $e->section->course_id);

        return $enrollments->map(function ($enrollment) {
            $totalEarned = 0;
            $totalPossible = 0;
            foreach ($enrollment->grades as $grade) {
                $totalEarned += (float) $grade->score;
                $totalPossible += (float) $grade->assessment->max_points;
            }
            $gradePercent = $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 1) : null;

            return [
                'course' => $enrollment->section->course,
                'term' => $enrollment->section->term,
                'grade_percent' => $gradePercent,
            ];
        });
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.certificates.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Certificates') }}
        </flux:button>
        <div class="flex items-center gap-3">
            <flux:heading size="xl">{{ $certificate->certificate_number }}</flux:heading>
            <flux:badge size="sm" :color="$certificate->status()->color()">
                {{ $certificate->status()->label() }}
            </flux:badge>
        </div>
    </div>

    <div class="mb-6 grid gap-6 sm:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('Certificate Information') }}</flux:heading>
            <dl class="space-y-2">
                <div>
                    <flux:text variant="subtle" class="text-xs">{{ __('Student') }}</flux:text>
                    <flux:text>{{ $certificate->student->name }}</flux:text>
                </div>
                <div>
                    <flux:text variant="subtle" class="text-xs">{{ __('Student ID') }}</flux:text>
                    <flux:text>{{ $certificate->student->studentProfile?->student_id_number ?? '—' }}</flux:text>
                </div>
                <div>
                    <flux:text variant="subtle" class="text-xs">{{ __('Program') }}</flux:text>
                    <flux:text>{{ $certificate->program->name }} ({{ $certificate->program->code }})</flux:text>
                </div>
                <div>
                    <flux:text variant="subtle" class="text-xs">{{ __('Issued') }}</flux:text>
                    <flux:text>{{ $certificate->issued_at->format('M j, Y') }}</flux:text>
                </div>
                <div>
                    <flux:text variant="subtle" class="text-xs">{{ __('Issued By') }}</flux:text>
                    <flux:text>{{ $certificate->issuer?->name ?? '—' }}</flux:text>
                </div>
                @if ($certificate->isRevoked())
                    <div>
                        <flux:text variant="subtle" class="text-xs">{{ __('Revoked') }}</flux:text>
                        <flux:text>{{ $certificate->revoked_at->format('M j, Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text variant="subtle" class="text-xs">{{ __('Revoked By') }}</flux:text>
                        <flux:text>{{ $certificate->revoker?->name ?? '—' }}</flux:text>
                    </div>
                    @if ($certificate->notes)
                        <div>
                            <flux:text variant="subtle" class="text-xs">{{ __('Revocation Notes') }}</flux:text>
                            <flux:text>{{ $certificate->notes }}</flux:text>
                        </div>
                    @endif
                @endif
            </dl>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('Completed Courses') }}</flux:heading>
            <div class="space-y-2">
                @foreach ($this->completedCourses as $item)
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text>{{ $item['course']->code }} — {{ $item['course']->name }}</flux:text>
                            <flux:text variant="subtle" class="text-xs">{{ $item['term']->name }}</flux:text>
                        </div>
                        <flux:text>{{ $item['grade_percent'] !== null ? $item['grade_percent'].'%' : __('N/A') }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if (! $certificate->isRevoked() && auth()->user()->can('certificates.manage'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
            <flux:heading size="sm" class="mb-3 text-red-700 dark:text-red-400">{{ __('Revoke Certificate') }}</flux:heading>
            <flux:text class="mb-3">{{ __('This action is permanent and cannot be undone.') }}</flux:text>
            <div class="mb-3 max-w-lg">
                <flux:textarea wire:model="revokeNotes" placeholder="{{ __('Optional: reason for revocation...') }}" rows="2" />
            </div>
            <flux:button variant="danger" wire:click="revokeCertificate" wire:confirm="{{ __('Are you sure you want to revoke this certificate? This action cannot be undone.') }}">
                {{ __('Revoke Certificate') }}
            </flux:button>
        </div>
    @endif
</section>
