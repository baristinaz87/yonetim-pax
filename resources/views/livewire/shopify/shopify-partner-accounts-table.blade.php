<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap justify-between items-center gap-4">
        <div class="flex flex-wrap gap-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-72 pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Hesap ara...">
            </div>
            <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="">Tüm Durumlar</option>
                <option value="active">Aktif</option>
                <option value="inactive">Pasif</option>
            </select>
        </div>
        <a href="{{ route('shopify.partner-accounts.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/></svg>
            Yeni Partner Hesabı
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        <button wire:click="sortBy('name')" class="flex items-center">
                            Ad
                            @if($sortField === 'name')<span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3">Org ID</th>
                    <th scope="col" class="px-6 py-3">API Versiyonu</th>
                    <th scope="col" class="px-6 py-3 text-center">Uygulama</th>
                    <th scope="col" class="px-6 py-3">Durum</th>
                    <th scope="col" class="px-6 py-3">Notlar</th>
                    <th scope="col" class="px-6 py-3 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <a href="{{ $account->partnerUrl() }}" target="_blank" class="text-blue-600 hover:underline">{{ $account->name }}</a>
                        </th>
                        <td class="px-6 py-4 font-mono text-xs">{{ $account->org_id }}</td>
                        <td class="px-6 py-4">{{ $account->api_version }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $account->apps_count }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($account->active)
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Aktif</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Pasif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <div class="truncate" title="{{ $account->notes }}">{{ $account->notes ?? '—' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('shopify.partner-accounts.edit', $account->id) }}" class="text-blue-600 hover:text-blue-900" title="Düzenle">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                </a>
                                <button wire:click="toggleActive({{ $account->id }})" class="text-yellow-600 hover:text-yellow-900" title="{{ $account->active ? 'Pasif Yap' : 'Aktif Yap' }}">
                                    @if($account->active)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                    @endif
                                </button>
                                <button wire:click="deleteAccount({{ $account->id }})" wire:confirm="Bu partner hesabını silmek istediğinizden emin misiniz?" class="text-red-600 hover:text-red-900" title="Sil">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            Henüz partner hesabı yok.
                            <a href="{{ route('shopify.partner-accounts.create') }}" class="text-blue-600 hover:underline">İlk hesabı oluşturun →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $accounts->links() }}
    </div>
</div>