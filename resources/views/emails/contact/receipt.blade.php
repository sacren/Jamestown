<x-mail::message>
# {{ __('Thanks for reaching out, :name', ['name' => $senderName]) }}

{{ __('We received your message and will get back to you as soon as we can. Here is a copy for your records.') }}

**{{ __('Subject') }}:** {{ $subjectLine }}

---

{{ $body }}

---

{{ __('In the meantime, feel free to explore our programs.') }}

<x-mail::button :url="route('public.programs')">
{{ __('Browse programs') }}
</x-mail::button>

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
