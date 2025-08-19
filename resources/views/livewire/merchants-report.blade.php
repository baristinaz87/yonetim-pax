<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <div wire:click="selectStatusFilter('new')" class="bg-white border border-gray-200 rounded-2xl p-4 cursor-pointer">
        <div class="text-sm text-gray-500">Yeni Müşteriler</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $data["new"] ?? 0 }}</div>
    </div>
    <div wire:click="selectStatusFilter('on_track')" class="bg-white border border-gray-200 rounded-2xl p-4 cursor-pointer">
        <div class="text-sm text-gray-500">Takipteki Müşteriler</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $data["on_track"] ?? 0 }}</div>
    </div>
    <div wire:click="selectStatusFilter('wait_activation')" class="bg-white border border-gray-200 rounded-2xl p-4 cursor-pointer">
        <div class="text-sm text-gray-500">Akt. Bekleyenler</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $data["wait_activation"] ?? 0 }}</div>
    </div>
    <div wire:click="selectStatusFilter('wait_deactivation')" class="bg-white border border-gray-200 rounded-2xl p-4 cursor-pointer">
        <div class="text-sm text-gray-500">Deakt. Bekleyenler</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $data["wait_deactivation"] ?? 0 }}</div>
    </div>
    <div wire:click="selectStatusFilter('credit_expiring')" class="bg-white border border-gray-200 rounded-2xl p-4 cursor-pointer">
        <div class="text-sm text-gray-500">Kontör Tarihi Yaklaşan</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $data["credit_expiring"] ?? 0 }}</div>
    </div>
    <div wire:click="selectStatusFilter('credit_expired')" class="bg-white border border-gray-200 rounded-2xl p-4 cursor-pointer">
        <div class="text-sm text-gray-500">Kontörü Biten</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $data["credit_expired"] ?? 0 }}</div>
    </div>
</div>
