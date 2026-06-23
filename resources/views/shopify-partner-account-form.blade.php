<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($accountId) ? __('Partner Hesabını Düzenle') : __('Yeni Partner Hesabı') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <livewire:shopify.shopify-partner-account-form :accountId="$accountId ?? null" />
        </div>
    </div>
</x-app-layout>