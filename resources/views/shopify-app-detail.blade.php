<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Uygulama Detayı') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="text-gray-900">
                <livewire:shopify.shopify-app-detail :appId="$appId" />
            </div>
        </div>
    </div>
</x-app-layout>
