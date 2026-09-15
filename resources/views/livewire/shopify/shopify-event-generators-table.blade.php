<div class="space-y-6">
    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg"><div class="p-6 text-gray-900">
        <div class="mb-5 flex items-center justify-between gap-4"><div><h3 class="text-lg font-bold">{{ $editingId ? 'Event Oluşturucu Düzenle' : 'Yeni Event Oluşturucu' }}</h3><p class="text-sm text-gray-500">Uygulama verisindeki koşullar sağlanınca Shopify event oluşturur.</p></div>@if($editingId)<button wire:click="resetForm" type="button" class="text-sm text-gray-600 underline">Yeni oluşturucuya dön</button>@endif</div>
        <form wire:submit.prevent="save" class="space-y-5">
            <div class="grid gap-4 lg:grid-cols-4">
                <div><label class="mb-1 block text-sm font-medium">Ad</label><input wire:model.defer="form.name" type="text" class="block w-full rounded border-gray-300" placeholder="Örn: Kredi Azaldı">@error('form.name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium">Handle</label><input wire:model.defer="form.handle" type="text" class="block w-full rounded border-gray-300 font-mono" placeholder="out_of_credit">@error('form.handle')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium">Cooldown (dakika)</label><input wire:model.defer="form.cooldown_minutes" type="number" min="0" class="block w-full rounded border-gray-300"><p class="mt-1 text-xs text-gray-500">0: her kontrolde event oluşturabilir.</p>@error('form.cooldown_minutes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium">Veri tazeliği (dk)</label><input wire:model.defer="form.max_data_age_minutes" type="number" min="1" class="block w-full rounded border-gray-300" placeholder="Sınırsız"><p class="mt-1 text-xs text-gray-500">Eski veride event üretmez.</p>@error('form.max_data_age_minutes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div><label class="mb-2 block text-sm font-medium">Uygulamalar</label><div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">@foreach($apps as $app)<label class="flex items-center gap-2 rounded border border-gray-200 px-3 py-2 text-sm"><input wire:model.defer="form.app_ids" type="checkbox" value="{{ $app->id }}" class="rounded border-gray-300 text-blue-600"><span>{{ $app->name }}</span><span class="font-mono text-xs text-gray-400">{{ $app->handle }}</span></label>@endforeach</div>@error('form.app_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div class="rounded border border-gray-200 p-4">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><div><h4 class="font-semibold">Koşullar</h4><p class="text-xs text-gray-500">Alan yolu: <code>credit</code> veya <code>subscription.status</code>.</p></div><div class="flex items-center gap-2 text-sm"><span>Mantık</span><select wire:model.defer="form.condition_logic" class="rounded border-gray-300 text-sm"><option value="all">Tümü sağlanmalı (AND)</option><option value="any">Birisi sağlanmalı (OR)</option></select></div></div>
                <div class="space-y-3">
                    @foreach($form['conditions'] as $index => $condition)
                        <div wire:key="condition-{{ $index }}" class="rounded bg-gray-50 p-3">
                            <div class="space-y-2">
                                <div class="grid gap-2 lg:grid-cols-2 md:grid-cols-4">
                                    <input wire:model.defer="form.conditions.{{ $index }}.path" class="w-full rounded border-gray-300 text-sm md:col-span-3" placeholder="Alan yolu (örn. credit)">
                                    <select wire:model.live="form.conditions.{{ $index }}.value_type" class="w-full rounded border-gray-300 text-sm">
                                        <option value="number">Sayı</option>
                                        <option value="date">Tarih</option>
                                    </select>
                                </div>
                                <select wire:model.defer="form.conditions.{{ $index }}.operator" class="w-full rounded border-gray-300 text-sm">
                                    <option value="equals">eşittir</option>
                                    <option value="not_equals">eşit değildir</option>
                                    <option value="gt">büyüktür</option>
                                    <option value="gte">büyük/eşit</option>
                                    <option value="lt">küçüktür</option>
                                    <option value="lte">küçük/eşit</option>
                                </select>
                                @if($condition['value_type'] === 'date')
                                    <div class="inline-flex rounded border border-gray-300 p-0.5 text-sm">
                                        <button wire:click="setDateValueMode({{ $index }}, 'fixed')" type="button" class="rounded px-3 py-1.5 {{ $condition['value_mode'] === 'fixed' ? 'bg-blue-600 text-white' : 'text-gray-700' }}">Sabit zaman</button>
                                        <button wire:click="setDateValueMode({{ $index }}, 'dynamic')" type="button" class="rounded px-3 py-1.5 {{ $condition['value_mode'] === 'dynamic' ? 'bg-blue-600 text-white' : 'text-gray-700' }}">Dinamik zaman</button>
                                    </div>
                                    @if($condition['value_mode'] === 'dynamic')
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-sm text-gray-700">Şimdi</span>
                                            <input wire:model.defer="form.conditions.{{ $index }}.value" type="number" step="1" class="w-28 rounded border-gray-300 text-sm" placeholder="± gün">
                                            <span class="text-sm text-gray-700">gün</span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Pozitif değer gelecek, negatif değer geçmiş günleri ifade eder.</p>
                                    @else
                                        <input wire:model.defer="form.conditions.{{ $index }}.value" type="datetime-local" step="60" class="mt-2 w-full rounded border-gray-300 text-sm" placeholder="değer">
                                    @endif
                                @else
                                    <input wire:model.defer="form.conditions.{{ $index }}.value" type="number" step="any" class="w-full rounded border-gray-300 text-sm" placeholder="değer">
                                @endif
                            </div>
                            <div class="mt-3 text-right"><button wire:click="removeCondition({{ $index }})" type="button" class="px-2 py-1 text-sm text-red-600">Sil</button></div>
                            @error("form.conditions.$index.path")<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            @error("form.conditions.$index.value")<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <button wire:click="addCondition" type="button" class="mt-3 text-sm text-blue-600">+ Koşul ekle</button>
            </div>
            <div class="rounded border border-gray-200 p-4"><h4 class="mb-3 font-semibold">Çalışma zamanlaması</h4><div class="grid gap-4 lg:grid-cols-2"><div><label class="mb-1 block text-sm font-medium">Başlangıç</label><input wire:model.defer="form.schedule.start_time" type="time" class="w-full rounded border-gray-300"></div><div><label class="mb-1 block text-sm font-medium">Bitiş</label><input wire:model.defer="form.schedule.end_time" type="time" class="w-full rounded border-gray-300"></div></div><div class="mt-3 flex flex-wrap gap-3 text-sm">@foreach([1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'] as $day => $label)<label class="flex items-center gap-1"><input wire:model.defer="form.schedule.days" type="checkbox" value="{{ $day }}" class="rounded border-gray-300">{{ $label }}</label>@endforeach</div><p class="mt-2 text-xs text-gray-500">Saatler Europe/Istanbul'a göre değerlendirilir. Günler boşsa her gün, saatler boşsa gün boyu çalışır. Geceyi aşan aralıklar desteklenir.</p>@error('form.schedule.start_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div class="flex items-center justify-between gap-4"><label class="flex items-center gap-2 text-sm"><input wire:model.defer="form.active" type="checkbox" class="rounded border-gray-300 text-blue-600">Aktif</label><button type="submit" class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">{{ $editingId ? 'Güncelle' : 'Kaydet' }}</button></div>
        </form>
    </div></div>
    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg"><div class="p-6">@if (session()->has('message')) <div wire:key="event-generator-flash-{{ $flashId }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition class="mb-4 flex items-center justify-between gap-3 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700"><span>{{ session('message') }}</span><button type="button" x-on:click="show = false" class="text-lg leading-none" aria-label="Mesajı kapat">&times;</button></div> @endif<h3 class="mb-4 text-lg font-bold">Event Oluşturucular</h3><div class="overflow-x-auto"><table class="w-full text-left text-sm text-gray-500"><thead class="bg-gray-50 text-xs uppercase text-gray-700"><tr><th class="px-4 py-3">Ad</th><th class="px-4 py-3">Koşullar</th><th class="px-4 py-3">Uygulamalar</th><th class="px-4 py-3">Cooldown</th><th class="px-4 py-3">Durum</th><th class="px-4 py-3 text-right">İşlemler</th></tr></thead><tbody>@forelse($generators as $generator)<tr class="border-b bg-white hover:bg-gray-50"><td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $generator->name }}</div><div class="font-mono text-xs text-gray-400">{{ $generator->handle }}</div></td><td class="px-4 py-3"><div class="mb-1 text-xs font-medium text-gray-700">{{ $generator->condition_logic === 'all' ? 'Tüm koşullar sağlanmalı (AND)' : 'Koşullardan biri sağlanmalı (OR)' }}</div><div class="space-y-1">@foreach($generator->conditions ?? [] as $condition)<div class="text-xs text-gray-600"><span class="font-mono text-gray-800">{{ $condition['path'] ?? '-' }}</span><span class="text-gray-400">({{ ($condition['value_type'] ?? 'number') === 'date' ? 'Tarih-saat' : 'Sayı' }})</span> <span>{{ ['equals' => 'eşittir', 'not_equals' => 'eşit değildir', 'gt' => 'büyüktür', 'gte' => 'büyük/eşit', 'lt' => 'küçüktür', 'lte' => 'küçük/eşit'][$condition['operator'] ?? ''] ?? ($condition['operator'] ?? '-') }}</span> <span class="font-medium text-gray-800">{{ $this->formatConditionValue($condition) }}</span></div>@endforeach</div></td><td class="px-4 py-3 text-xs">{{ collect($generator->app_ids ?? [])->map(fn ($id) => $appNamesById[$id] ?? '#'.$id)->implode(', ') }}</td><td class="px-4 py-3">{{ $generator->cooldown_minutes }} dk</td><td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs {{ $generator->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ $generator->active ? 'Aktif' : 'Pasif' }}</span></td><td class="px-4 py-3 text-right"><div class="inline-flex gap-2"><button wire:click="preview({{ $generator->id }})" class="text-purple-600">Önizle</button><button wire:click="runNow({{ $generator->id }})" class="text-green-600">Çalıştır</button><button wire:click="edit({{ $generator->id }})" class="text-blue-600">Düzenle</button><button wire:click="duplicate({{ $generator->id }})" class="text-indigo-600">Çoğalt</button><button wire:click="toggleActive({{ $generator->id }})" class="text-yellow-600">{{ $generator->active ? 'Pasif' : 'Aktif' }}</button><button wire:click="delete({{ $generator->id }})" wire:confirm="Bu oluşturucu silinsin mi?" class="text-red-600">Sil</button></div></td></tr>@empty<tr><td colspan="6" class="px-6 py-8 text-center">Henüz event oluşturucu yok.</td></tr>@endforelse</tbody></table></div></div></div>
</div>
