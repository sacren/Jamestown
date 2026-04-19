<x-layouts.error
    code="503"
    :heading="__('Back in a moment')"
    :message="__(':brand is temporarily offline for scheduled maintenance. Please check back in a few minutes.', ['brand' => config('app.name')])"
/>
