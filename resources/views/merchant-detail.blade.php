<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Müşteri Detayı') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="p-6 text-gray-900">
                <livewire:merchant-detail-form :id="request('id')" />
            </div>
        </div>
    </div>
</x-app-layout>

