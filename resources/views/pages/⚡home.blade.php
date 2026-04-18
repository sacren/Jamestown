<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::marketing')] #[Title('Home')] class extends Component {}; ?>

<div>
    <x-marketing.section>
        <p class="text-center text-zinc-600 dark:text-zinc-400">
            {{ __('Welcome to') }} {{ config('app.name') }}.
        </p>
    </x-marketing.section>
</div>
