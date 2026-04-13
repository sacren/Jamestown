<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Academics')" class="grid">
                    <flux:sidebar.item icon="rectangle-stack" :href="route('catalog.programs')" :current="request()->routeIs('catalog.*')" wire:navigate>
                        {{ __('Program Catalog') }}
                    </flux:sidebar.item>

                    @role('student')
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('registration.sections')" :current="request()->routeIs('registration.sections')" wire:navigate>
                        {{ __('Registration') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="route('registration.schedule')" :current="request()->routeIs('registration.schedule')" wire:navigate>
                        {{ __('My Schedule') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="check-badge" :href="route('registration.attendance')" :current="request()->routeIs('registration.attendance')" wire:navigate>
                        {{ __('My Attendance') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('registration.grades')" :current="request()->routeIs('registration.grades')" wire:navigate>
                        {{ __('My Grades') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('registration.invoices.index')" :current="request()->routeIs('registration.invoices.*')" wire:navigate>
                        {{ __('My Billing') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-check" :href="route('registration.certificates.index')" :current="request()->routeIs('registration.certificates.*')" wire:navigate>
                        {{ __('My Certificates') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('registration.transcript')" :current="request()->routeIs('registration.transcript')" wire:navigate>
                        {{ __('My Transcript') }}
                    </flux:sidebar.item>
                    @endrole
                </flux:sidebar.group>

                @role('instructor')
                <flux:sidebar.group :heading="__('Teaching')" class="grid">
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('instructor.sections')" :current="request()->routeIs('instructor.*')" wire:navigate>
                        {{ __('My Sections') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endrole

                @role('super-admin|admin|registrar')
                <flux:sidebar.group :heading="__('Administration')" class="grid">
                    @can('manage-users')
                    <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-programs')
                    <flux:sidebar.item icon="academic-cap" :href="route('admin.programs.index')" :current="request()->routeIs('admin.programs.*')" wire:navigate>
                        {{ __('Programs') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-courses')
                    <flux:sidebar.item icon="book-open" :href="route('admin.courses.index')" :current="request()->routeIs('admin.courses.*')" wire:navigate>
                        {{ __('Courses') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-terms')
                    <flux:sidebar.item icon="calendar" :href="route('admin.terms.index')" :current="request()->routeIs('admin.terms.*')" wire:navigate>
                        {{ __('Terms') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-rooms')
                    <flux:sidebar.item icon="building-office" :href="route('admin.rooms.index')" :current="request()->routeIs('admin.rooms.*')" wire:navigate>
                        {{ __('Rooms') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-sections')
                    <flux:sidebar.item icon="table-cells" :href="route('admin.sections.index')" :current="request()->routeIs('admin.sections.*')" wire:navigate>
                        {{ __('Sections') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-enrollments')
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.enrollments.index')" :current="request()->routeIs('admin.enrollments.*')" wire:navigate>
                        {{ __('Enrollments') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-attendance')
                    <flux:sidebar.item icon="check-badge" :href="route('admin.attendance.index')" :current="request()->routeIs('admin.attendance.*')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('manage-grades')
                    <flux:sidebar.item icon="chart-bar" :href="route('admin.grades.index')" :current="request()->routeIs('admin.grades.*')" wire:navigate>
                        {{ __('Grades') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('invoices.view-any')
                    <flux:sidebar.item icon="banknotes" :href="route('admin.invoices.index')" :current="request()->routeIs('admin.invoices.*')" wire:navigate>
                        {{ __('Billing') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('certificates.view-any')
                    <flux:sidebar.item icon="document-check" :href="route('admin.certificates.index')" :current="request()->routeIs('admin.certificates.*')" wire:navigate>
                        {{ __('Certificates') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endrole
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
