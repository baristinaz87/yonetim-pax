<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="bg-white shadow-md rounded-lg p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="partner_account_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Partner Hesabı <span class="text-red-500">*</span>
                </label>
                <select wire:model="partner_account_id" id="partner_account_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Seçiniz —</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" @if(!$partner->active) disabled @endif>
                            {{ $partner->name }} ({{ $partner->org_id }}){{ !$partner->active ? ' — Pasif' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('partner_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Yeni partner hesabı eklemek için
                    <a href="{{ route('shopify.partner-accounts.create') }}" target="_blank" class="text-blue-600 hover:underline">buraya tıklayın</a>.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Durum</label>
                <label class="inline-flex items-center mt-2">
                    <input wire:model="active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Aktif</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Görünen Ad <span class="text-red-500">*</span></label>
                <input wire:model="name" type="text" id="name" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="ÖR: Yurtici Kargo">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="handle" class="block text-sm font-medium text-gray-700 mb-1">Handle <span class="text-red-500">*</span></label>
                <input wire:model="handle" type="text" id="handle" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="ÖR: yurtici-kargo">
                @error('handle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Kebab-case, sadece harf/rakam/tire. URL'de görünür.</p>
            </div>
        </div>

        <div>
            <label for="shopify_app_gid" class="block text-sm font-medium text-gray-700 mb-1">
                Shopify App GID <span class="text-gray-400 text-xs">(senkronizasyon için gerekli)</span>
            </label>
            <input wire:model="shopify_app_gid" type="text" id="shopify_app_gid" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="gid://partners/App/4645385">
            @error('shopify_app_gid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">Partner Dashboard → GraphQL Explorer: <code class="font-mono">{ apps { edges { node { id handle } } } }</code></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">OAuth Client ID <span class="text-red-500">*</span></label>
                <input wire:model="client_id" type="text" id="client_id" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="abc123def456...">
                @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="client_secret" class="block text-sm font-medium text-gray-700 mb-1">
                    OAuth Client Secret
                    @if($isEditing)<span class="text-gray-400 text-xs">(boş bırakırsanız mevcut korunur)</span>@else<span class="text-red-500">*</span>@endif
                </label>
                <input wire:model="client_secret" type="password" id="client_secret" class="block w-full font-mono border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="shpss_xxx..." autocomplete="off">
                @error('client_secret') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
            <input wire:model="logo" type="text" id="logo" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://...">
            @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-1">
                Webhook URL <span class="text-gray-400 text-xs">(install/uninstall POST bildirimi için)</span>
            </label>
            <input wire:model="webhook_url" type="url" id="webhook_url" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://delivery.paxdigital.net/webhooks/shopify/...">
            @error('webhook_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">
                Boş bırakırsanız dış sisteme bildirim gönderilmez. Bu uygulama için install/uninstall olayları yine de işlenir, sadece HTTP POST atlanır.
            </p>
        </div>

        {{-- delivery.paxdigital.net API konfigürasyonu --}}
        <fieldset class="border border-gray-200 rounded-lg p-4 space-y-4">
            <legend class="text-sm font-semibold text-gray-700 px-2">delivery.paxdigital.net API</legend>
            <p class="text-xs text-gray-500 -mt-2">
                Shop başına Shopify access token almak için kullanılır. Önce auth endpoint'inden bearer token alınır,
                sonra get-access-token endpoint'ine <code class="font-mono">?shop=...</code> parametresi ile istek atılır.
                HTTP timeout sabit <strong>20 saniye</strong>'dir.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="api_auth_endpoint" class="block text-sm font-medium text-gray-700 mb-1">
                        API Auth Endpoint
                    </label>
                    <input wire:model="api_auth_endpoint" type="url" id="api_auth_endpoint" class="block w-full font-mono text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://delivery.paxdigital.net/api/login">
                    @error('api_auth_endpoint') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Bearer token alınacak tam URL (login endpoint).</p>
                </div>

                <div>
                    <label for="get_access_token_endpoint" class="block text-sm font-medium text-gray-700 mb-1">
                        Get Access Token Endpoint
                    </label>
                    <input wire:model="get_access_token_endpoint" type="url" id="get_access_token_endpoint" class="block w-full font-mono text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://delivery.paxdigital.net/api/get-password-by-shop">
                    @error('get_access_token_endpoint') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Shop başına token döndüren tam URL.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="auth_email" class="block text-sm font-medium text-gray-700 mb-1">Auth Email</label>
                    <input wire:model="auth_email" type="email" id="auth_email" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="ops@paxdigital.net">
                    @error('auth_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="auth_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Auth Password
                        @if($isEditing)<span class="text-gray-400 text-xs">(boş bırakırsanız mevcut korunur)</span>@endif
                    </label>
                    <input wire:model="auth_password" type="password" id="auth_password" class="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="{{ $isEditing ? '•••••••• (değiştirmek için yazın)' : '••••••••' }}" autocomplete="off">
                    @error('auth_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('shopify.apps') }}" class="text-gray-600 hover:text-gray-900">← Geri</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded inline-flex items-center gap-2">
                <svg wire:loading wire:target="save" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                {{ $isEditing ? 'Güncelle' : 'Oluştur' }}
            </button>
        </div>
    </form>
</div>