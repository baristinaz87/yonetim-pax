@php use Carbon\Carbon; @endphp

<div class="space-y-6">
    {{-- Başlık + Mağaza Bilgileri --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold text-gray-900 font-mono">{{ $store->domain }}</h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $store->name ?? '—' }}
                </p>
                <div class="flex flex-wrap gap-3 mt-2 text-xs text-gray-500">
                    @if($store->shop_id)
                        <span><strong class="text-gray-700">Shop ID:</strong> {{ $store->shop_id }}</span>
                    @endif
                    @if($store->plan_display_name || $store->plan_name)
                        <span><strong class="text-gray-700">Plan:</strong> {{ $store->plan_display_name ?? $store->plan_name }}</span>
                    @endif
                    @if($store->country)
                        <span><strong class="text-gray-700">Ülke:</strong> {{ $store->country }} ({{ $store->country_code }})</span>
                    @endif
                    @if($store->currency)
                        <span><strong class="text-gray-700">Para:</strong> {{ $store->currency }}</span>
                    @endif
                    @if($store->timezone)
                        <span><strong class="text-gray-700">TZ:</strong> {{ $store->timezone }}</span>
                    @endif
                </div>
            </div>

            <div class="shrink-0">
                <a href="{{ route('shopify.stores.index') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-3 py-2 rounded-lg">
                    ← Mağazalara Dön
                </a>
            </div>
        </div>

        {{-- Detay Bilgileri --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 text-sm">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.94 6.5A2 2 0 002 8.5v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-.94-1.5l-7-4.5a2 2 0 00-2.12 0l-7 4.5z"/>
                    </svg>
                    <span class="text-gray-600">{{ $store->email ?? '—' }}</span>
                </div>
                @if($store->contact_email && $store->contact_email !== $store->email)
                    <div class="flex items-center gap-2 pl-6 text-xs text-gray-500">
                        Contact: <span>{{ $store->contact_email }}</span>
                    </div>
                @endif
                @if($store->phone)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 4a2 2 0 012-2h2.5a1 1 0 01.8.4l1.4 2.1a1 1 0 01-.2 1.3L7 7.5a12 12 0 005 5l1.7-1.5a1 1 0 011.3-.2l2.1 1.4a1 1 0 01.4.8V15a2 2 0 01-2 2h-1C7.8 17 3 12.2 3 6V5a2 2 0 01-.001-.001z"/>
                        </svg>
                        {{ $store->phone }}
                    </div>
                @endif
                @if($store->shop_owner)
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        {{ $store->shop_owner }}
                    </div>
                @endif
            </div>

            <div class="space-y-1">
                @if($store->address1 || $store->city)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-gray-600 text-xs">
                            @if($store->address1) {{ $store->address1 }}<br> @endif
                            @if($store->city || $store->zip)
                                {{ $store->zip }} {{ $store->city }}
                            @endif
                        </div>
                    </div>
                @endif
                @if($store->language)
                    <div class="text-gray-600">
                        <strong class="text-gray-700 text-xs">Dil:</strong>
                        <span class="text-xs">{{ $store->language }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- İstatistik Kartları --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Toplam Kurulum</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">{{ $installedApps->count() }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-xs text-green-600 font-medium uppercase tracking-wider">Aktif Uygulama</p>
                <p class="text-2xl font-bold text-green-900 mt-1">{{ $activeCount }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-xs text-red-600 font-medium uppercase tracking-wider">Kaldırılan</p>
                <p class="text-2xl font-bold text-red-900 mt-1">{{ $uninstallCount }}</p>
            </div>
        </div>
    </div>

    {{-- Kurulu Uygulamalar --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Kurulu Uygulamalar</h3>

        @if($installedApps->isEmpty())
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-8 text-center">
                <p class="text-sm text-gray-500">Bu mağaza henüz hiçbir uygulamayı kurmamış.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($installedApps as $sa)
                    @if($sa->app)
                        <a href="{{ route('shopify.apps.show', $sa->app->id) }}"
                           wire:navigate
                           class="flex items-center gap-3 p-3 border {{ $sa->status === 'active' ? 'border-green-200 bg-green-50/40' : 'border-gray-200 bg-gray-50/40' }} rounded-lg hover:shadow-sm transition">
                            @if($sa->app->logo)
                                <img src="{{ $sa->app->logo }}" alt="{{ $sa->app->name }}"
                                     class="w-12 h-12 rounded-lg object-contain bg-white border border-gray-200 shrink-0" loading="lazy">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-lg font-bold shrink-0">
                                    {{ mb_strtoupper(mb_substr($sa->app->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $sa->app->name }}</p>
                                <p class="text-xs text-gray-500 font-mono truncate">{{ $sa->app->handle }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    @if($sa->status === 'active')
                                        <span class="inline-flex items-center gap-1 text-[11px] px-1.5 py-0.5 rounded bg-green-100 text-green-800 border border-green-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Kaldırıldı
                                        </span>
                                    @endif
                                    <span class="text-[11px] text-gray-400">
                                        {{ $sa->installed_at?->format('d/m/Y') }}
                                        @if($sa->uninstalled_at)
                                            → {{ $sa->uninstalled_at->format('d/m/Y') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Event Timeline --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-bold text-gray-900">Event Timeline</h3>
            <div class="flex flex-wrap gap-2">
                <select wire:model.live="eventTypeFilter"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">Tüm Tipler</option>
                    <option value="installed">Sadece Kurulum</option>
                    <option value="uninstalled">Sadece Kaldırma</option>
                </select>
                <select wire:model.live="appFilter"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="0">Tüm Uygulamalar</option>
                    @foreach($allApps as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
                @if($eventTypeFilter !== '' || $appFilter > 0)
                    <button wire:click="clearFilters" class="text-xs text-gray-500 hover:text-gray-700 underline">
                        Temizle
                    </button>
                @endif
            </div>
        </div>

        @if($events->isEmpty())
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-8 text-center">
                <p class="text-sm text-gray-500">Bu mağaza için event kaydı bulunmuyor.</p>
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
                            @if($event->app)
                                <div class="flex items-center gap-2">
                                    @if($event->app->logo)
                                        <img src="{{ $event->app->logo }}" alt="{{ $event->app->name }}"
                                             class="w-7 h-7 rounded object-contain bg-white border border-gray-200 shrink-0" loading="lazy">
                                    @else
                                        <div class="w-7 h-7 rounded bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ mb_strtoupper(mb_substr($event->app->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <a href="{{ route('shopify.apps.show', $event->app->id) }}"
                                       wire:navigate
                                       class="text-sm font-semibold {{ $isInstall ? 'text-green-900 hover:text-green-700' : 'text-red-900 hover:text-red-700' }} hover:underline">
                                        {{ $event->app->name }}
                                    </a>
                                </div>
                            @endif
                            <p class="text-sm {{ $isInstall ? 'text-green-800' : 'text-red-800' }} mt-1">
                                <strong>{{ $isInstall ? 'KURULDU' : 'KALDIRILDI' }}</strong>
                                @if($event->label)
                                    · <span class="text-xs text-gray-600">{{ $event->label }}</span>
                                @endif
                            </p>
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
</div>
