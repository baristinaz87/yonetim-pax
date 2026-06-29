<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    {{-- Aktif Uygulama Filtresi Bildirimi --}}
    @if($filteringApp)
        <div class="mb-4 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-lg p-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm">
                @if($filteringApp->logo)
                    <img src="{{ $filteringApp->logo }}" class="w-6 h-6 rounded object-contain bg-white border border-gray-200" alt="">
                @else
                    <div class="w-6 h-6 rounded bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-xs font-bold">
                        {{ mb_strtoupper(mb_substr($filteringApp->name, 0, 1)) }}
                    </div>
                @endif
                <span>
                    <strong>{{ $filteringApp->name }}</strong> uygulamasını kuran mağazalar filtreleniyor.
                </span>
            </div>
            <button wire:click="clearAppFilter" class="text-xs text-indigo-700 hover:text-indigo-900 underline">
                Filtreyi kaldır
            </button>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap justify-between items-center gap-4">
        <div class="flex flex-wrap gap-3">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                       class="block w-72 pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Domain, mağaza adı, e-posta...">
            </div>

            <select wire:model.live="appFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="0">Tüm Uygulamalar</option>
                @foreach($apps as $a)
                    <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->handle }})</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="">Tüm Durumlar</option>
                <option value="has_active">Aktif Kurulumu Olan</option>
                <option value="no_active">Aktif Kurulumu Olmayan</option>
            </select>
        </div>

        <div class="text-xs text-gray-500">
            Toplam <span class="font-semibold text-gray-700">{{ $stores->total() }}</span> mağaza
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3">
                        <button wire:click="sortBy('domain')" class="flex items-center">
                            Domain
                            @if($sortField === 'domain')<span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif
                        </button>
                    </th>
                    <th scope="col" class="px-4 py-3">Mağaza Adı</th>
                    <th scope="col" class="px-4 py-3">Plan / Ülke</th>
                    <th scope="col" class="px-4 py-3 text-center">Kurulumlar</th>
                    <th scope="col" class="px-4 py-3 text-center">Aktif</th>
                    <th scope="col" class="px-4 py-3 text-center">Event</th>
                    <th scope="col" class="px-4 py-3">Son Sync</th>
                    <th scope="col" class="px-4 py-3 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stores as $store)
                    <tr class="bg-white border-t hover:bg-gray-50">
                        <th scope="row" class="px-4 py-3 font-mono text-xs text-gray-900 whitespace-nowrap">
                            {{ $store->domain }}
                        </th>
                        <td class="px-4 py-3">
                            <div class="text-gray-900 truncate max-w-[200px]">
                                {{ $store->name ?? '—' }}
                            </div>
                            @if($store->shop_owner)
                                <div class="text-xs text-gray-400 truncate max-w-[200px]">{{ $store->shop_owner }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <div class="text-gray-700">{{ $store->plan_display_name ?? $store->plan_name ?? '—' }}</div>
                            <div class="text-gray-400">{{ $store->country ?? '—' }} · {{ $store->currency ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold text-gray-700">{{ $store->apps_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($store->active_apps_count > 0)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded bg-green-100 text-green-800 border border-green-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    {{ $store->active_apps_count }}
                                </span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($store->events_count > 0)
                                <span class="inline-flex items-center text-xs text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">
                                    {{ $store->events_count }}
                                </span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $store->updated_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('shopify.stores.show', $store->id) }}"
                               wire:navigate
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 text-xs">
                                Detay
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                            @if($appFilter > 0)
                                Seçili uygulamayı kuran mağaza bulunamadı.
                            @else
                                Henüz mağaza kaydı yok.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $stores->links() }}
    </div>
</div>
