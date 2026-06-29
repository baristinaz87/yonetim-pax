<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mağaza Detayı') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="text-gray-900">
                <livewire:shopify.shopify-store-detail :storeId="$storeId" />
            </div>
        </div>
    </div>
</x-app-layout>
