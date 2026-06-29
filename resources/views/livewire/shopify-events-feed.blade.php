@php use Carbon\Carbon; @endphp

<div class="mt-8">
    {{-- Başlık + Filtreler --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/>
                    <path d="M10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                </svg>
                Events
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
                Mağazalardaki uygulama kurulum/kaldırma olayları (install / uninstall).
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="typeFilter"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="">Tüm Tipler</option>
                <option value="installed">Kurulum</option>
                <option value="uninstalled">Kaldırma</option>
            </select>

            <select wire:model.live="appFilter"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="0">Tüm Uygulamalar</option>
                @foreach($apps as $a)
                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                @endforeach
            </select>

            @if($typeFilter !== '' || $appFilter > 0)
                <button wire:click="clearFilters"
                        class="text-xs text-gray-500 hover:text-gray-700 underline">
                    Filtreleri temizle
                </button>
            @endif
        </div>
    </div>

    {{-- Event Listesi --}}
    @if($events->isEmpty())
        <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-8 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 11.293a1 1 0 101.414 1.414l2-2A1 1 0 0011 10V7z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-gray-500">Henüz event kaydı bulunmuyor.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <ul class="divide-y divide-gray-100">
                @foreach($events as $event)
                    @php
                        $isInstall = $event->type === 'installed';
                        $rowBg     = $isInstall
                            ? 'bg-gradient-to-r from-green-50/70 via-white to-white hover:from-green-50'
                            : 'bg-gradient-to-r from-red-50/70 via-white to-white hover:from-red-50';

                        $badgeClasses = $isInstall
                            ? 'bg-green-100 text-green-800 border-green-200'
                            : 'bg-red-100 text-red-800 border-red-200';

                        $dotClasses = $isInstall ? 'bg-green-500' : 'bg-red-500';
                        $dotRing   = $isInstall ? 'ring-green-200' : 'ring-red-200';
                    @endphp

                    <li class="flex flex-wrap items-center gap-3 px-4 py-3 {{ $rowBg }} transition-colors">
                        {{-- Uygulama --}}
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            @if($event->app)
                                @if($event->app->logo)
                                    <img src="{{ $event->app->logo }}"
                                         alt="{{ $event->app->name }}"
                                         class="w-9 h-9 rounded-lg object-contain bg-gray-50 border border-gray-200 shrink-0"
                                         loading="lazy">
                                @else
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-sm font-bold shrink-0">
                                        {{ mb_strtoupper(mb_substr($event->app->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('shopify.apps.show', $event->app->id) }}"
                                       wire:navigate
                                       class="block text-sm font-medium text-gray-900 hover:text-blue-700 truncate">
                                        {{ $event->app->name }}
                                    </a>
                                    <span class="block text-xs text-gray-400 font-mono truncate">{{ $event->app->handle }}</span>
                                </div>
                            @else
                                <div class="w-9 h-9 rounded-lg bg-gray-200 text-gray-500 flex items-center justify-center text-xs shrink-0">?</div>
                                <span class="text-sm text-gray-500 italic">Silinmiş uygulama</span>
                            @endif
                        </div>

                        {{-- Event Badge --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border {{ $badgeClasses }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }} ring-4 {{ $dotRing }}"></span>
                                {{ $isInstall ? 'KURULDU' : 'KALDIRILDI' }}
                            </span>
                        </div>

                        {{-- Mağaza --}}
                        @if($event->store)
                            <div class="shrink-0 min-w-0">
                                <a href="{{ route('shopify.stores.show', $event->store->id) }}"
                                   wire:navigate
                                   class="inline-flex items-center gap-1.5 text-sm text-blue-700 hover:text-blue-900 hover:underline">
                                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="truncate font-mono text-xs">{{ $event->store->domain }}</span>
                                </a>
                                @if($event->store->name && $event->store->name !== $event->store->domain)
                                    <span class="block text-[11px] text-gray-400 truncate">{{ $event->store->name }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">— mağaza yok —</span>
                        @endif

                        {{-- Zaman --}}
                        <div class="shrink-0 text-right ml-auto">
                            <span class="block text-xs text-gray-700" title="{{ $event->created_at }}">
                                {{ $event->created_at->format('d/m/Y H:i') }}
                            </span>
                            <span class="block text-[11px] text-gray-400">
                                {{ $event->created_at->diffForHumans(null, true) }} önce
                            </span>
                        </div>

                        {{-- Etiket varsa --}}
                        @if(! empty($event->label))
                            <div class="basis-full text-xs text-gray-500 pl-12 italic">
                                {{ $event->label }}
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-3">
            {{ $events->links() }}
        </div>
    @endif
</div>
