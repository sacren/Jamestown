<?php

use App\Concerns\ProgramValidationRules;
use App\Models\Program;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Program')] class extends Component {
    use ProgramValidationRules;

    #[Locked]
    public int $programId;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $duration_weeks = null;
    public ?int $total_credits_required = null;
    public ?float $tuition_cost = null;
    public bool $is_active = true;

    public function mount(Program $program): void
    {
        $this->programId = $program->id;
        $this->name = $program->name;
        $this->code = $program->code;
        $this->description = $program->description ?? '';
        $this->duration_weeks = $program->duration_weeks;
        $this->total_credits_required = $program->total_credits_required;
        $this->tuition_cost = (float) $program->tuition_cost;
        $this->is_active = $program->is_active;
    }

    public function updateProgram(): void
    {
        $validated = $this->validate($this->programUpdateRules($this->programId));

        $program = Program::findOrFail($this->programId);

        $program->update([
            ...$validated,
            'code' => strtoupper($validated['code']),
        ]);

        $this->dispatch('program-updated');
    }

    public function deleteProgram(): void
    {
        Program::findOrFail($this->programId)->delete();

        $this->redirect(route('admin.programs.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.programs.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Programs') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit Program') }}</flux:heading>
        <flux:subheading>{{ __('Update program information') }}</flux:subheading>
    </div>

    <form wire:submit="updateProgram" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Program Name')" type="text" required />
        <flux:input wire:model="code" :label="__('Program Code')" type="text" required />
        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
        <flux:input wire:model="duration_weeks" :label="__('Duration (weeks)')" type="number" min="1" max="104" required />
        <flux:input wire:model="total_credits_required" :label="__('Total Credits Required')" type="number" min="1" max="200" required />
        <flux:input wire:model="tuition_cost" :label="__('Tuition Cost ($)')" type="number" step="0.01" min="0" required />
        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Update Program') }}</flux:button>

            <x-action-message class="me-3" on="program-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

    <div class="mt-12">
        <flux:separator />
        <div class="mt-6">
            <flux:heading size="lg">{{ __('Delete Program') }}</flux:heading>
            <flux:subheading>{{ __('Permanently remove this program and all associated courses.') }}</flux:subheading>
            <flux:button variant="danger" wire:click="deleteProgram" wire:confirm="{{ __('Are you sure you want to delete this program? All associated courses will also be deleted. This action cannot be undone.') }}" class="mt-4">
                {{ __('Delete Program') }}
            </flux:button>
        </div>
    </div>
</section>
