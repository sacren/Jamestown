<x-layouts::auth :title="__('Register')">
    @php
        $interestedProgram = null;

        if ($programId = session('interested_program')) {
            $interestedProgram = \App\Models\Program::query()
                ->where('id', $programId)
                ->where('is_active', true)
                ->first();

            if ($interestedProgram) {
                session()->reflash();
            }
        }
    @endphp

    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create your account')" :description="__('Tell us about yourself to get started.')" />

        @if ($interestedProgram)
            <flux:callout icon="academic-cap" color="blue" data-test="interested-program-banner">
                <flux:callout.heading>{{ __('Applying to :name', ['name' => $interestedProgram->name]) }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __("Create your account to continue. We'll pick up where you left off after you sign in.") }}
                </flux:callout.text>
            </flux:callout>
        @endif

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
