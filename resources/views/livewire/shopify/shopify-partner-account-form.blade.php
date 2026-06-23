<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="bg-white shadow-md rounded-lg p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Hesap Adı <span class="text-red-500">*</span></label>
                <input wire:model="name" type="text" id="name" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="ÖR: PaxDigital Production">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="org_id" class="block text-sm font-medium text-gray-700 mb-1">Organization ID <span class="text-red-500">*</span></label>
                <input wire:model="org_id" type="text" id="org_id" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="ÖR: 1779760">
                @error('org_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Shopify Partners paneli URL'sindeki sayı: <code class="font-mono">partners.shopify.com/{org_id}</code></p>
            </div>
        </div>

        <div>
            <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">
                Access Token
                @if($isEditing)<span class="text-gray-400 text-xs">(boş bırakırsanız mevcut token korunur)</span>@else<span class="text-red-500">*</span>@endif
            </label>
            <input wire:model="access_token" type="password" id="access_token" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="shpat_xxx..." autocomplete="off">
            @error('access_token') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">Partner Dashboard → Settings → Access tokens</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="api_version" class="block text-sm font-medium text-gray-700 mb-1">API Versiyonu</label>
                <input wire:model="api_version" type="text" id="api_version" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="2026-04">
                @error('api_version') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Durum</label>
                <label class="inline-flex items-center mt-2">
                    <input wire:model="active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Aktif</span>
                </label>
            </div>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notlar</label>
            <textarea wire:model="notes" id="notes" rows="3" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Hesabın amacı / kullanım yeri hakkında notlar..."></textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('shopify.partner-accounts') }}" class="text-gray-600 hover:text-gray-900">← Geri</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded inline-flex items-center gap-2">
                <svg wire:loading wire:target="save" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                {{ $isEditing ? 'Güncelle' : 'Oluştur' }}
            </button>
        </div>
    </form>
</div>