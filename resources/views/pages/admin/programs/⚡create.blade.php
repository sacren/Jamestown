<?php

use App\Concerns\ProgramValidationRules;
use App\Models\Program;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Program')] class extends Component {
    use ProgramValidationRules;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $duration_weeks = null;
    public ?int $total_credits_required = null;
    public ?float $tuition_cost = null;
    public bool $is_active = true;

    public function createProgram(): void
    {
        $validated = $this->validate($this->programCreateRules());

        Program::create([
            ...$validated,
            'code' => strtoupper($validated['code']),
        ]);

        $this->redirect(route('admin.programs.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.programs.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Programs') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create Program') }}</flux:heading>
        <flux:subheading>{{ __('Add a new academic program') }}</flux:subheading>
    </div>

    <form wire:submit="createProgram" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Program Name')" type="text" required />
        <flux:input wire:model="code" :label="__('Program Code')" type="text" required placeholder="e.g. WLD, HVAC" />
        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
        <flux:input wire:model="duration_weeks" :label="__('Duration (weeks)')" type="number" min="1" max="104" required />
        <flux:input wire:model="total_credits_required" :label="__('Total Credits Required')" type="number" min="1" max="200" required />
        <flux:input wire:model="tuition_cost" :label="__('Tuition Cost ($)')" type="number" step="0.01" min="0" required />
        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create Program') }}</flux:button>
        </div>
    </form>
</section>
