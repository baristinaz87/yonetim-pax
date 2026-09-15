<div class="p-6 bg-white shadow-sm sm:rounded-lg">
    <div class="flex items-center justify-between mx-2 mb-4">
        <div class="text-xl font-bold">
            Flow İşlem Kayıtları
        </div>

        <div class="flex items-center gap-3">
            @if(count($selected) > 0)
                <span class="text-sm text-gray-500">
                    {{ count($selected) }} kayıt seçildi
                </span>

                <button
                    type="button"
                    wire:click="retrySelected"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="retrySelected">
                        Yeniden Dene
                    </span>

                    <span wire:loading wire:target="retrySelected">
                        İşleniyor...
                    </span>
                </button>
            @endif

            <select
                wire:model.live="status"
                class="border-gray-300 rounded-md text-sm"
            >
                <option value="">Tüm Durumlar</option>
                <option value="pending">Bekliyor</option>
                <option value="processing">İşleniyor</option>
                <option value="done">Tamamlandı</option>
                <option value="failed">Başarısız</option>
                <option value="try_again">Yeniden Denendi</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">

            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th class="px-4 py-3 w-10"></th>

                <th class="px-6 py-3">
                    Shop
                </th>

                <th class="px-6 py-3">
                    Targets
                </th>

                <th class="px-6 py-3">
                    Flow
                </th>

                <th class="px-6 py-3">
                    Event
                </th>

                <th class="px-6 py-3">
                    Kanal
                </th>

                <th class="px-6 py-3">
                    Şablon
                </th>

                <th class="px-6 py-3">
                    Planlanan
                </th>

                <th class="px-6 py-3">
                    Durum
                </th>

            </tr>
            </thead>

            <tbody>
            @forelse($transactions as $transaction)

                @php
                    $flowName =
                        $transaction->flow?->name
                        ?? $transaction->flow?->title
                        ?? data_get($transaction->flow_snapshot, 'name')
                        ?? 'Flow';

                    $eventName =
                        $transaction->event?->name
                        ?? $transaction->event?->label
                        ?? $transaction->event?->type
                        ?? 'Event';

                    $templateName = match ($transaction->channel) {
                        'whatsapp' => $wpTemplateNamesById[$transaction->template_id] ?? null,
                        'email' => $emailTemplateNamesById[$transaction->template_id] ?? null,
                        default => null,
                    };
                @endphp

                <tr
                    wire:key="flow-transaction-{{ $transaction->id }}"
                    class="bg-white border-b border-gray-200 hover:bg-gray-50"
                >

                    {{-- Select --}}
                    <td class="px-4 py-4">
                        @if($transaction->status === 'failed')
                            <input
                                type="checkbox"
                                wire:model.live="selected"
                                value="{{ $transaction->id }}"
                                class="w-4 h-4 border-gray-300 rounded text-blue-600 focus:ring-blue-500"
                            >
                        @endif
                    </td>

                    {{-- Shop --}}
                    <td class="px-6 py-4">
                        @if($transaction->event?->store)
                            <a
                                href="{{ route('shopify.stores.show', $transaction->event->store->id) }}"
                                wire:navigate
                                class="font-medium text-blue-700 hover:text-blue-900 hover:underline"
                            >
                                {{ $transaction->event->store->domain ?: $transaction->event->store->name }}
                            </a>
                        @else
                            <span class="text-xs text-gray-400 italic">Mağaza bulunamadı</span>
                        @endif

                    </td>

                    {{-- Targets --}}
                    <td class="px-6 py-4">
                        @if(!empty($transaction->targets))
                            <div class="max-w-xs truncate text-xs text-gray-600" title="{{ implode(', ', $transaction->targets) }}">
                                {{ implode(', ', $transaction->targets) }}
                            </div>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>

                    {{-- Flow --}}
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">
                                {{ $flowName }}
                            </span>

                            <span class="text-xs text-gray-400 font-mono">
                                #{{ $transaction->flow_id }}
                            </span>
                        </div>
                    </td>

                    {{-- Event --}}
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">
                                {{ $eventName }}
                            </span>

                            <span class="text-xs text-gray-400 font-mono">
                                #{{ $transaction->event_id }}
                            </span>
                        </div>
                    </td>

                    {{-- Kanal --}}
                    <td class="px-6 py-4">
                        {{ $transaction->channel ?? '-' }}
                    </td>

                    {{-- Şablon --}}
                    <td class="px-6 py-4">
                        @if($templateName)
                            <div class="font-medium text-gray-900">{{ $templateName }}</div>
                            <div class="text-xs text-gray-400 font-mono">#{{ $transaction->template_id }}</div>
                        @else
                            {{ $transaction->template_id ?? '-' }}
                        @endif
                    </td>

                    {{-- Planlanan --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $transaction->scheduled_at?->format('d/m/Y H:i:s') ?? '-' }}
                    </td>

                    {{-- Durum --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        @switch($transaction->status)

                            @case('pending')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-800">
                                    Bekliyor
                                </span>
                                @break

                            @case('processing')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                    İşleniyor
                                </span>
                                @break

                            @case('done')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">
                                    Tamamlandı
                                </span>
                                @break

                            @case('failed')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800">
                                    Başarısız
                                </span>
                                @break

                            @case('try_again')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800">
                                    Yeniden Denendi
                                </span>
                                @break

                            @default
                                <span class="inline-flex px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">
                                    {{ $transaction->status }}
                                </span>

                        @endswitch
                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="9"
                        class="px-6 py-8 text-center text-gray-400"
                    >
                        Flow işlem kaydı bulunamadı.
                    </td>
                </tr>

            @endforelse
            </tbody>

        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
