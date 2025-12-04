@props(['title' => 'Export Options'])

<x-mary-modal {{ $attributes }} title="{{ $title }}" class="backdrop-blur" box-class="bg-blue-400 border-primary/20 border w-full max-w-lg">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 rounded-lg border border-base-200">
            <x-mary-button label="Download PDF" icon="o-document-text" wire:click="export('pdf')" class="btn-ghost justify-start" />
            <x-mary-button label="Download CSV" icon="o-table-cells" wire:click="export('csv')" class="btn-ghost justify-start" />
        </div>
        <div class="p-4 rounded-lg border border-base-200">
            <x-mary-button label="Download DOCX" icon="o-document" wire:click="export('docx')" class="btn-ghost justify-start" />
            <x-mary-button label="Download DOC" icon="o-document" wire:click="export('doc')" class="btn-ghost justify-start" />
        </div>
    </div>
    <x-slot:actions>
        <x-mary-button label="Close" @click="$wire.showExportModal = false" />
    </x-slot:actions>
</x-mary-modal>

