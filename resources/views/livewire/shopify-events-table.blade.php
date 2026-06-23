@php use Carbon\Carbon; @endphp

<div class="bg-[#1a1a1a] text-gray-200 shadow-sm sm:rounded-lg m-6 p-6 border border-gray-800">
    {{-- Başlık --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-xl font-bold text-white">Shopify Aktivitesi</h3>
            @if($store)
                <p class="text-xs text-gray-400 mt-1">
                    Mağaza: <span class="text-gray-300">{{ $store->name ?? $store->domain }}</span>
                    · {{ $store->domain }}
                    @if($store->email)
                        · <span class="text-gray-500">{{ $store->email }}</span>
                    @endif
                </p>
            @endif
        </div>
        <div class="flex gap-2">
            <span class="text-xs px-2 py-1 rounded bg-blue-900/40 text-blue-300 border border-blue-800">
                Son 30 gün
            </span>
        </div>
    </div>

    @if(! $shopDomain)
        <div class="rounded-lg bg-yellow-900/30 border border-yellow-700 text-yellow-200 p-4 text-sm">
            Bu müşterinin Shopify mağaza domain'i tanımlı değil.
        </div>
    @elseif(! $store)
        <div class="rounded-lg bg-gray-800 border border-gray-700 text-gray-300 p-4 text-sm">
            <strong>{{ $shopDomain }}</strong> domain'i Shopify veritabanında bulunamadı.
            Henüz hiçbir uygulamayı kurmamış olabilir veya sync henüz çalışmamış olabilir.
        </div>
    @else
        {{-- Kurulu Uygulamalar Tablosu --}}
        @if($apps->isNotEmpty())
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-white mb-3">Uygulama Durumu</h4>
                <div class="overflow-x-auto rounded-lg border border-gray-800">
                    <table class="w-full text-sm">
                        <thead class="bg-[#0f0f0f] text-xs uppercase text-gray-400">
                            <tr>
                                <th class="px-4 py-3 text-left">Uygulama</th>
                                <th class="px-4 py-3 text-left">Durum</th>
                                <th class="px-4 py-3 text-left">Kurulum</th>
                                <th class="px-4 py-3 text-left">Kaldırma</th>
                                <th class="px-4 py-3 text-left">Token</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apps as $sa)
                                <tr class="border-t border-gray-800 hover:bg-[#0f0f0f]">
                                    <td class="px-4 py-3">
                                        <span class="text-white font-medium">{{ $sa->app->name ?? '—' }}</span>
                                        <span class="text-xs text-gray-500 block">{{ $sa->app->handle ?? '' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($sa->status === 'active')
                                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-green-900/40 text-green-300 border border-green-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-gray-800 text-gray-400 border border-gray-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                                Kaldırıldı
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs">
                                        {{ $sa->installed_at ? $sa->installed_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs">
                                        {{ $sa->uninstalled_at ? $sa->uninstalled_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        @if($sa->access_token)
                                            <span class="text-green-400">✓</span>
                                        @else
                                            <span class="text-gray-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Event Timeline --}}
        @if($events->isNotEmpty())
            <div>
                <h4 class="text-sm font-semibold text-white mb-3">Event Timeline</h4>
                <div class="space-y-2">
                    @foreach($events as $event)
                        <div class="flex items-start gap-3 bg-[#0f0f0f] border border-gray-800 rounded-lg p-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($event->type === 'installed')
                                    <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                @else
                                    <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-white">
                                    @if($event->app)
                                        <span class="font-medium">{{ $event->app->name }}</span>
                                    @else
                                        <span class="text-gray-500">Bilinmeyen uygulama</span>
                                    @endif
                                    <span class="text-gray-400">
                                        · {{ $event->type === 'installed' ? 'kuruldu' : 'kaldırıldı' }}
                                    </span>
                                </p>
                                @if($event->label)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $event->label }}</p>
                                @endif
                            </div>
                            <div class="flex-shrink-0 text-xs text-gray-500">
                                {{ $event->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($totalEvents > $perPage)
                    @php
                        $window = 2;
                        $start = max(1, $page - $window);
                        $end = min($lastPage, $start + $window * 2);
                        $start = max(1, $end - $window * 2);
                    @endphp
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-800">
                        <div class="text-xs text-gray-500">
                            <span class="font-semibold text-gray-300">{{ $totalEvents }}</span> event'ten
                            <span class="font-semibold text-gray-300">
                                {{ ($page - 1) * $perPage + 1 }}-{{ min($page * $perPage, $totalEvents) }}
                            </span>
                            arası
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="setPage(1)"
                                    class="px-2 py-1 text-xs rounded bg-[#0f0f0f] border border-gray-800 text-gray-300 hover:bg-gray-800 disabled:opacity-40"
                                    {{ $page <= 1 ? 'disabled' : '' }}>
                                «
                            </button>
                            <button wire:click="setPage({{ max(1, $page - 1) }})"
                                    class="px-2 py-1 text-xs rounded bg-[#0f0f0f] border border-gray-800 text-gray-300 hover:bg-gray-800 disabled:opacity-40"
                                    {{ $page <= 1 ? 'disabled' : '' }}>
                                ‹
                            </button>
                            @for($p = $start; $p <= $end; $p++)
                                <button wire:click="setPage({{ $p }})"
                                        class="px-3 py-1 text-xs rounded border {{ $p === $page ? 'bg-blue-600 border-blue-500 text-white' : 'bg-[#0f0f0f] border-gray-800 text-gray-300 hover:bg-gray-800' }}">
                                    {{ $p }}
                                </button>
                            @endfor
                            <button wire:click="setPage({{ min($lastPage, $page + 1) }})"
                                    class="px-2 py-1 text-xs rounded bg-[#0f0f0f] border border-gray-800 text-gray-300 hover:bg-gray-800 disabled:opacity-40"
                                    {{ $page >= $lastPage ? 'disabled' : '' }}>
                                ›
                            </button>
                            <button wire:click="setPage({{ $lastPage }})"
                                    class="px-2 py-1 text-xs rounded bg-[#0f0f0f] border border-gray-800 text-gray-300 hover:bg-gray-800 disabled:opacity-40"
                                    {{ $page >= $lastPage ? 'disabled' : '' }}>
                                »
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-lg bg-[#0f0f0f] border border-gray-800 text-gray-500 p-6 text-center text-sm">
                Henüz event kaydı yok.
            </div>
        @endif
    @endif
</div>
