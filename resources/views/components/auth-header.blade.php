@props([
    'title',
    'description',
])

<x-mary-header
    :title="$title"
    :subtitle="$description"
    size="text-2xl"
    class="mb-6 text-center"
/>
