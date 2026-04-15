<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Program Completion Report')] class extends Component {
    #[Url]
    public string $programId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view-reports'), 403);
    }

    #[Computed]
    public function programs()
    {
        return Program::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function overallStats(): array
    {
        $activePrograms = Program::where('is_active', true)->count();

        $totalCertificates = Certificate::query()
            ->whereNull('revoked_at')
            ->when($this->programId, fn ($q) => $q->where('program_id', $this->programId))
            ->count();

        $totalStudents = Enrollment::query()
            ->whereHas('section.course', function ($q) {
                $q->whereHas('program', fn ($p) => $p->where('is_active', true));
                if ($this->programId) {
                    $q->where('program_id', $this->programId);
                }
            })
            ->distinct()
            ->count('user_id');

        $completionRate = $totalStudents > 0 ? round($totalCertificates / $totalStudents * 100, 1) : 0;

        return [
            'active_programs' => $activePrograms,
            'total_certificates' => $totalCertificates,
            'total_students' => $totalStudents,
            'completion_rate' => $completionRate,
        ];
    }

    #[Computed]
    public function programStats()
    {
        $programs = $this->programId
            ? Program::where('id', $this->programId)->where('is_active', true)->get()
            : Program::where('is_active', true)->orderBy('name')->get();

        return $programs->map(function ($program) {
            $totalStudents = Enrollment::query()
                ->whereHas('section.course', fn ($q) => $q->where('program_id', $program->id))
                ->distinct()
                ->count('user_id');

            $certificatesIssued = Certificate::where('program_id', $program->id)->whereNull('revoked_at')->count();
            $certificatesRevoked = Certificate::where('program_id', $program->id)->whereNotNull('revoked_at')->count();
            $inProgress = max(0, $totalStudents - $certificatesIssued);
            $completionRate = $totalStudents > 0 ? round($certificatesIssued / $totalStudents * 100, 1) : 0;

            return (object) [
                'program' => $program,
                'total_students' => $totalStudents,
                'in_progress' => $inProgress,
                'certificates_issued' => $certificatesIssued,
                'certificates_revoked' => $certificatesRevoked,
                'completion_rate' => $completionRate,
            ];
        });
    }

    #[Computed]
    public function recentCertificates()
    {
        return Certificate::query()
            ->when($this->programId, fn ($q) => $q->where('program_id', $this->programId))
            ->with(['student', 'program', 'issuer'])
            ->latest('issued_at')
            ->take(10)
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-sm">
            <a href="{{ route('admin.reports.index') }}" wire:navigate class="text-blue-500 hover:underline">{{ __('Reports') }}</a>
            <span class="text-zinc-400">/</span>
            <span>{{ __('Program Completion Report') }}</span>
        </div>
        <flux:heading size="xl">{{ __('Program Completion Report') }}</flux:heading>
        <flux:subheading>{{ __('Completion rates, certificate counts, and program progress') }}</flux:subheading>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="programId" placeholder="{{ __('All Programs') }}">
                <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
                @foreach ($this->programs as $program)
                    <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Active Programs') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['active_programs'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Certificates Issued') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['total_certificates'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Students in Programs') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['total_students'] }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle" class="text-xs uppercase">{{ __('Completion Rate') }}</flux:text>
            <flux:heading size="lg">{{ $this->overallStats['completion_rate'] }}%</flux:heading>
        </div>
    </div>

    @if ($this->programStats->isNotEmpty())
        <div class="mb-6">
            <flux:heading size="lg" class="mb-3">{{ __('By Program') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Program') }}</flux:table.column>
                    <flux:table.column>{{ __('Students') }}</flux:table.column>
                    <flux:table.column>{{ __('In Progress') }}</flux:table.column>
                    <flux:table.column>{{ __('Completed') }}</flux:table.column>
                    <flux:table.column>{{ __('Completion Rate') }}</flux:table.column>
                    <flux:table.column>{{ __('Revoked') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->programStats as $row)
                        @php
                            $color = $row->completion_rate >= 50 ? 'green' : ($row->completion_rate >= 25 ? 'amber' : 'zinc');
                        @endphp
                        <flux:table.row>
                            <flux:table.cell variant="strong">
                                <div>{{ $row->program->name }}</div>
                                <flux:text variant="subtle" class="text-xs">{{ $row->program->code }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->total_students }}</flux:table.cell>
                            <flux:table.cell>{{ $row->in_progress }}</flux:table.cell>
                            <flux:table.cell>{{ $row->certificates_issued }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$color" inset="top bottom">{{ $row->completion_rate }}%</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->certificates_revoked }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <flux:callout>
            <flux:callout.heading>{{ __('No active programs') }}</flux:callout.heading>
            <flux:callout.text>{{ __('No active programs found.') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($this->recentCertificates->isNotEmpty())
        <div class="mb-6">
            <flux:heading size="lg" class="mb-3">{{ __('Recent Certificates') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Student') }}</flux:table.column>
                    <flux:table.column>{{ __('Program') }}</flux:table.column>
                    <flux:table.column>{{ __('Certificate #') }}</flux:table.column>
                    <flux:table.column>{{ __('Issued') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->recentCertificates as $cert)
                        @php
                            $status = $cert->status();
                            $statusColor = $status->color();
                        @endphp
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $cert->student->name }}</flux:table.cell>
                            <flux:table.cell>{{ $cert->program->name }}</flux:table.cell>
                            <flux:table.cell>{{ $cert->certificate_number }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $cert->issued_at->format('M j, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$statusColor" inset="top bottom">{{ $status->label() }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</section>
