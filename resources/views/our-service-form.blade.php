<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($serviceId) ? __('Hizmet Düzenle') : __('Hizmet Ekle') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <livewire:our-service-form :serviceId="$serviceId ?? null" />
        </div>
    </div>
</x-app-layout>
