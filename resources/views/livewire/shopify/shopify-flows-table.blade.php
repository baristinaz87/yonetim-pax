<div class="space-y-6">
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <div class="flex items-center justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">{{ $editingId ? 'Akış Düzenle' : 'Yeni Akış' }}</h3>
                    <p class="text-sm text-gray-500">Shopify event oluşunca seçilen kanallardan Brevo bildirimi gönderilir.</p>
                </div>

                @if($editingId)
                    <button wire:click="resetForm" type="button" class="text-sm text-gray-600 hover:text-gray-900 underline">
                        Yeni akışa dön
                    </button>
                @endif
            </div>

            <form wire:submit.prevent="save" class="space-y-5">
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akış Adı</label>
                        <input wire:model.defer="form.name" type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" placeholder="Örn: Kurulum sonrası hoş geldin">
                        @error('form.name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                        <select wire:model.defer="form.event_type" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                            <option value="installed">Kuruldu</option>
                            <option value="uninstalled">Kaldırıldı</option>
                        </select>
                        @error('form.event_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Delay Dakika</label>
                        <input wire:model.defer="form.delay_minutes" type="number" min="0" max="43200" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        @error('form.delay_minutes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Uygulamalar</label>
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($apps as $app)
                            <label class="flex items-center gap-2 rounded border border-gray-200 px-3 py-2 text-sm">
                                <input wire:model.defer="form.app_ids" type="checkbox" value="{{ $app->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-gray-800">{{ $app->name }}</span>
                                <span class="text-xs text-gray-400 font-mono">{{ $app->handle }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.app_ids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('form.app_ids.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded border border-gray-200 p-4">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                            <input wire:model.live="form.channels" type="checkbox" value="whatsapp" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            WhatsApp
                        </label>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">WP Template</label>
                            <input wire:model.defer="form.whatsapp_template_id" type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" placeholder="Template ID giriniz">
                            @error('form.whatsapp_template_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded border border-gray-200 p-4">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                            <input wire:model.live="form.channels" type="checkbox" value="email" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            Mail
                        </label>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mail Template</label>
                            <input wire:model.defer="form.email_template_id" type="number" min="1" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" placeholder="Template ID giriniz">
                            @error('form.email_template_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                @error('form.channels') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="flex items-center justify-between gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input wire:model.defer="form.active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Aktif
                    </label>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded">
                        {{ $editingId ? 'Güncelle' : 'Kaydet' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Akışlar</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Ad</th>
                            <th class="px-6 py-3">Event</th>
                            <th class="px-6 py-3">Kanallar</th>
                            <th class="px-6 py-3">Delay</th>
                            <th class="px-6 py-3">Durum</th>
                            <th class="px-6 py-3 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($flows as $flow)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $flow->name }}</div>
                                    @php
                                        $flowAppNames = collect($flow->app_ids ?? [])
                                            ->map(fn ($appId) => $appNamesById[$appId] ?? '#'.$appId)
                                            ->implode(', ');
                                    @endphp
                                    <div class="text-xs text-gray-400 cursor-help" title="{{ $flowAppNames }}">
                                        {{ count($flow->app_ids ?? []) }} uygulama
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ $flow->event_type === 'installed' ? 'Kuruldu' : 'Kaldırıldı' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($flow->channels ?? [] as $channel)
                                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $channel }}</span>
                                        @endforeach
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400">
                                        @if($flow->whatsapp_template_id)
                                            WP: {{ $flow->whatsapp_template_id }}
                                        @endif
                                        @if($flow->email_template_id)
                                            Mail: {{ $flow->email_template_id }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ $flow->delay_minutes }} dk</td>
                                <td class="px-6 py-4">
                                    @if($flow->active)
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Aktif</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Pasif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <button wire:click="edit({{ $flow->id }})" class="text-blue-600 hover:text-blue-900">Düzenle</button>
                                        <button wire:click="toggleActive({{ $flow->id }})" class="text-yellow-600 hover:text-yellow-900">{{ $flow->active ? 'Pasif' : 'Aktif' }}</button>
                                        <button wire:click="delete({{ $flow->id }})" wire:confirm="Bu akışı silmek istediğinizden emin misiniz?" class="text-red-600 hover:text-red-900">Sil</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Henüz akış yok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
