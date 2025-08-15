<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Hizmetlerimiz') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <livewire:our-services-table />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
