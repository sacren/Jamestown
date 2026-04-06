<?php

use App\Concerns\RoomValidationRules;
use App\Enums\RoomType;
use App\Models\Room;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Room')] class extends Component {
    use RoomValidationRules;

    public string $name = '';
    public string $code = '';
    public string $building = '';
    public ?int $capacity = null;
    public string $type = '';
    public string $description = '';
    public bool $is_active = true;

    public function createRoom(): void
    {
        $validated = $this->validate($this->roomCreateRules());

        Room::create($validated);

        $this->redirect(route('admin.rooms.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.rooms.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Rooms') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Create Room') }}</flux:heading>
        <flux:subheading>{{ __('Add a new room or facility') }}</flux:subheading>
    </div>

    <form wire:submit="createRoom" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Room Name')" type="text" required placeholder="e.g. Welding Shop A" />
        <flux:input wire:model="code" :label="__('Room Code')" type="text" required placeholder="e.g. WS-A" />
        <flux:input wire:model="building" :label="__('Building')" type="text" required placeholder="e.g. Trade Building" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="capacity" :label="__('Capacity')" type="number" min="1" max="500" required />
            <flux:select wire:model="type" :label="__('Type')" required>
                <flux:select.option value="">{{ __('Select type...') }}</flux:select.option>
                @foreach (RoomType::cases() as $roomType)
                    <flux:select.option value="{{ $roomType->value }}">{{ ucfirst($roomType->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create Room') }}</flux:button>
        </div>
    </form>
</section>
