<?php

use App\Concerns\RoomValidationRules;
use App\Enums\RoomType;
use App\Models\Room;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Room')] class extends Component {
    use RoomValidationRules;

    #[Locked]
    public int $roomId;

    public string $name = '';
    public string $code = '';
    public string $building = '';
    public ?int $capacity = null;
    public string $type = '';
    public string $description = '';
    public bool $is_active = true;

    public function mount(Room $room): void
    {
        $this->roomId = $room->id;
        $this->name = $room->name;
        $this->code = $room->code;
        $this->building = $room->building;
        $this->capacity = $room->capacity;
        $this->type = $room->type->value;
        $this->description = $room->description ?? '';
        $this->is_active = $room->is_active;
    }

    public function updateRoom(): void
    {
        $validated = $this->validate($this->roomUpdateRules($this->roomId));

        Room::findOrFail($this->roomId)->update($validated);

        $this->dispatch('room-updated');
    }

    public function deleteRoom(): void
    {
        Room::findOrFail($this->roomId)->delete();

        $this->redirect(route('admin.rooms.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('admin.rooms.index')" wire:navigate icon="arrow-left" class="mb-4">
            {{ __('Back to Rooms') }}
        </flux:button>
        <flux:heading size="xl">{{ __('Edit Room') }}</flux:heading>
        <flux:subheading>{{ __('Update room information') }}</flux:subheading>
    </div>

    <form wire:submit="updateRoom" class="w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Room Name')" type="text" required />
        <flux:input wire:model="code" :label="__('Room Code')" type="text" required />
        <flux:input wire:model="building" :label="__('Building')" type="text" required />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="capacity" :label="__('Capacity')" type="number" min="1" max="500" required />
            <flux:select wire:model="type" :label="__('Type')" required>
                @foreach (RoomType::cases() as $roomType)
                    <flux:select.option value="{{ $roomType->value }}">{{ ucfirst($roomType->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

        <flux:checkbox wire:model="is_active" :label="__('Active')" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Update Room') }}</flux:button>

            <x-action-message class="me-3" on="room-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>

    <div class="mt-12">
        <flux:separator />
        <div class="mt-6">
            <flux:heading size="lg">{{ __('Delete Room') }}</flux:heading>
            <flux:subheading>{{ __('Permanently remove this room.') }}</flux:subheading>
            <flux:button variant="danger" wire:click="deleteRoom" wire:confirm="{{ __('Are you sure you want to delete this room? This action cannot be undone.') }}" class="mt-4">
                {{ __('Delete Room') }}
            </flux:button>
        </div>
    </div>
</section>
