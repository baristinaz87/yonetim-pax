@php use Carbon\Carbon; @endphp

<div class="space-y-6">
    {{-- Başlık + Özet --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <div class="flex flex-wrap items-start gap-4">
            @if($app->logo)
                <img src="{{ $app->logo }}" alt="{{ $app->name }}"
                     class="w-16 h-16 rounded-xl object-contain bg-gray-50 border border-gray-200 shrink-0">
            @else
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-2xl font-bold shrink-0">
                    {{ mb_strtoupper(mb_substr($app->name, 0, 1)) }}
                </div>
            @endif

            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    {{ $app->name }}
                    @if($app->active)
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded">Aktif</span>
                    @else
                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded">Pasif</span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500 font-mono mt-1">{{ $app->handle }}</p>
                @if($app->partnerAccount)
                    <p class="text-sm text-gray-600 mt-1">
                        Partner:
                        <span class="text-blue-700 font-medium">{{ $app->partnerAccount->name }}</span>
                        <span class="text-gray-400 text-xs font-mono">({{ $app->partnerAccount->org_id }})</span>
                    </p>
                @endif
            </div>

            <div class="flex gap-2 shrink-0">
                <a href="{{ route('shopify.stores.index', ['app_id' => $app->id]) }}"
                   class="inline-flex items-center gap-1.5 text-sm bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V6a4 4 0 00-4-4z" clip-rule="evenodd"/>
                    </svg>
                    Mağazaları Gör
                </a>
                <a href="{{ route('shopify.apps.edit', $app->id) }}"
                   class="inline-flex items-center gap-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-3 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/>
                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    </svg>
                    Düzenle
                </a>
            </div>
        </div>

        {{-- İstatistik Kartları --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Toplam Kurulum</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">{{ $totalInstalls }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-xs text-green-600 font-medium uppercase tracking-wider">Aktif Kurulum</p>
                <p class="text-2xl font-bold text-green-900 mt-1">{{ $activeInstalls }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-xs text-red-600 font-medium uppercase tracking-wider">Kaldırılan</p>
                <p class="text-2xl font-bold text-red-900 mt-1">{{ $uninstallCount }}</p>
            </div>
        </div>
    </div>

    {{-- Mağazalar Tablosu --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Bu Uygulamayı Kuran/Kaldıran Mağazalar</h3>

        @if($stores->isEmpty())
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-8 text-center">
                <p class="text-sm text-gray-500">Bu uygulamayı henüz hiçbir mağaza kurmamış.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3">Mağaza</th>
                            <th scope="col" class="px-4 py-3">Durum</th>
                            <th scope="col" class="px-4 py-3">Kurulum</th>
                            <th scope="col" class="px-4 py-3">Kaldırma</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stores as $sa)
                            <tr class="bg-white border-t hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('shopify.stores.show', $sa->store->id) }}"
                                       wire:navigate
                                       class="text-blue-700 hover:text-blue-900 hover:underline font-mono text-xs">
                                        {{ $sa->store->domain }}
                                    </a>
                                    @if($sa->store->name && $sa->store->name !== $sa->store->domain)
                                        <span class="block text-xs text-gray-500">{{ $sa->store->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($sa->status === 'active')
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-green-100 text-green-800 border border-green-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-gray-100 text-gray-700 border border-gray-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                            Kaldırıldı
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $sa->installed_at ? $sa->installed_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $sa->uninstalled_at ? $sa->uninstalled_at->format('d/m/Y H:i') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Event Timeline --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-bold text-gray-900">Event Timeline</h3>
            <div class="flex gap-2">
                <select wire:model.live="eventTypeFilter"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">Tüm Tipler</option>
                    <option value="installed">Sadece Kurulum</option>
                    <option value="uninstalled">Sadece Kaldırma</option>
                </select>
            </div>
        </div>

        @if($events->isEmpty())
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-8 text-center">
                <p class="text-sm text-gray-500">Bu uygulama için event kaydı bulunmuyor.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($events as $event)
                    @php
                        $isInstall = $event->type === 'installed';
                    @endphp
                    <div class="flex items-start gap-3 rounded-lg p-3 border {{ $isInstall ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                        <div class="flex-shrink-0 mt-1">
                            @if($isInstall)
                                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm {{ $isInstall ? 'text-green-900' : 'text-red-900' }}">
                                @if($event->store)
                                    <a href="{{ route('shopify.stores.show', $event->store->id) }}"
                                       wire:navigate
                                       class="font-mono font-semibold hover:underline">
                                        {{ $event->store->domain }}
                                    </a>
                                @else
                                    <span class="text-gray-500 italic">bilinmeyen mağaza</span>
                                @endif
                                <span class="ml-1">
                                    mağazası bu uygulamayı
                                    <strong>{{ $isInstall ? 'kurdu' : 'kaldırdı' }}</strong>.
                                </span>
                            </p>
                            @if($event->label)
                                <p class="text-xs text-gray-600 mt-0.5">{{ $event->label }}</p>
                            @endif
                        </div>

                        <div class="flex-shrink-0 text-right">
                            <p class="text-xs text-gray-700">{{ $event->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-[11px] text-gray-500">{{ $event->created_at->diffForHumans(null, true) }} önce</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($totalEvents > $perPage)
                @php
                    $window = 2;
                    $start = max(1, $page - $window);
                    $end   = min($lastPage, $start + $window * 2);
                    $start = max(1, $end - $window * 2);
                @endphp
                <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-200">
                    <div class="text-xs text-gray-500">
                        <span class="font-semibold text-gray-700">{{ $totalEvents }}</span> event'ten
                        <span class="font-semibold text-gray-700">
                            {{ ($page - 1) * $perPage + 1 }}-{{ min($page * $perPage, $totalEvents) }}
                        </span> arası
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="setPage(1)" class="px-2 py-1 text-xs rounded bg-gray-100 border border-gray-300 text-gray-700 hover:bg-gray-200 disabled:opacity-40" {{ $page <= 1 ? 'disabled' : '' }}>«</button>
                        <button wire:click="setPage({{ max(1, $page - 1) }})" class="px-2 py-1 text-xs rounded bg-gray-100 border border-gray-300 text-gray-700 hover:bg-gray-200 disabled:opacity-40" {{ $page <= 1 ? 'disabled' : '' }}>‹</button>
                        @for($p = $start; $p <= $end; $p++)
                            <button wire:click="setPage({{ $p }})"
                                    class="px-3 py-1 text-xs rounded border {{ $p === $page ? 'bg-blue-600 border-blue-500 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                {{ $p }}
                            </button>
                        @endfor
                        <button wire:click="setPage({{ min($lastPage, $page + 1) }})" class="px-2 py-1 text-xs rounded bg-gray-100 border border-gray-300 text-gray-700 hover:bg-gray-200 disabled:opacity-40" {{ $page >= $lastPage ? 'disabled' : '' }}>›</button>
                        <button wire:click="setPage({{ $lastPage }})" class="px-2 py-1 text-xs rounded bg-gray-100 border border-gray-300 text-gray-700 hover:bg-gray-200 disabled:opacity-40" {{ $page >= $lastPage ? 'disabled' : '' }}>»</button>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Geri butonu --}}
    <div class="flex justify-start">
        <a href="{{ route('shopify.apps') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">
            ← Uygulamalara geri dön
        </a>
    </div>
</div>
