<x-mail::message>
# {{ __('New contact message') }}

{{ __('You received a new message through the contact form on :brand.', ['brand' => config('app.name')]) }}

**{{ __('From') }}:** {{ $senderName }} &lt;{{ $senderEmail }}&gt;
**{{ __('Subject') }}:** {{ $subjectLine }}

---

{{ $body }}

---

{{ __('Reply directly to this email to respond to the sender.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
