@php use Carbon\Carbon; @endphp
<div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
    <div class="mx-2 my-2 text-xl font-bold">Diğer Faturalar</div>

    <div class="flex flex-wrap items-end gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç</label>
            <input
                type="date"
                wire:model.defer="startDate"
                wire:change="applyDateFilter"
                class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bitiş</label>
            <input
                type="date"
                wire:model.defer="endDate"
                wire:change="applyDateFilter"
                class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
            />
        </div>
        <div>
            <button
                type="button"
                wire:click="resetFilters"
                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded hover:bg-gray-50"
            >
                Temizle
            </button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <button
            type="button"
            wire:click="setSource(null)"
            class="px-3 py-1.5 text-sm rounded-full border transition
                {{ $selectedSource === null
                    ? 'bg-blue-700 text-white border-blue-700'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
        >
            Tümü ({{ $source_counts["all"] ?? 0 }})
        </button>
        <button
            type="button"
            wire:click="setSource('coming')"
            class="px-3 py-1.5 text-sm rounded-full border transition
                {{ $selectedSource === 'coming'
                    ? 'bg-blue-700 text-white border-blue-700'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
        >
            Gelen Fatura ({{ $source_counts["coming"] ?? 0 }})
        </button>
        <button
            type="button"
            wire:click="setSource('earchive')"
            class="px-3 py-1.5 text-sm rounded-full border transition
                {{ $selectedSource === 'earchive'
                    ? 'bg-blue-700 text-white border-blue-700'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
        >
            Earşiv (Turkcell Panelden Kesilen) ({{ $source_counts["earchive"] ?? 0 }})
        </button>
        <button
            type="button"
            wire:click="setSource('efatura')"
            class="px-3 py-1.5 text-sm rounded-full border transition
                {{ $selectedSource === 'efatura'
                    ? 'bg-blue-700 text-white border-blue-700'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
        >
            Efatura (Turkcell Panelden Kesilen) ({{ $source_counts["efatura"] ?? 0 }})
        </button>
    </div>

    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">Fatura No</th>
            <th scope="col" class="px-6 py-3">Tarih</th>
            <th scope="col" class="px-6 py-3">Fatura Tipi</th>
        </tr>
        </thead>
        <tbody>
        @forelse($data as $invoice)
            <tr wire:key="invoice-{{ $invoice["id"] }}" class="bg-white border-b border-gray-200 hover:bg-gray-50">
                <td class="px-6 py-4 font-medium text-gray-900">
                    {{ $invoice["invoice_number"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ !empty($invoice["execution_date"]) ? Carbon::parse($invoice["execution_date"])->format('Y-m-d H:i') : "" }}
                </td>
                <td class="px-6 py-4">
                    @php
                        $source = $invoice["source"] ?? null;
                        $badgeClasses = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";
                        if ($source === "coming") {
                            $badge = "Gelen Fatura";
                            $badgeClasses .= " bg-green-100 text-green-800";
                        } elseif ($source === "earchive") {
                            $badge = "Earşiv (Turkcell Panelden Kesilen)";
                            $badgeClasses .= " bg-purple-100 text-purple-800";
                        } elseif ($source === "efatura") {
                            $badge = "Efatura (Turkcell Panelden Kesilen)";
                            $badgeClasses .= " bg-blue-100 text-blue-800";
                        } else {
                            $badge = $source ?? "-";
                            $badgeClasses .= " bg-gray-100 text-gray-800";
                        }
                    @endphp
                    <span class="{{ $badgeClasses }}">{{ $badge }}</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                    Kayıt bulunamadı.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    <nav class="flex items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
        <span class="text-sm font-normal text-gray-500 mb-4 md:mb-0 block w-full md:inline md:w-auto">
            <span class="font-semibold text-gray-900">{{ $total_records }}</span> kayıttan <span
                class="font-semibold text-gray-900">{{ ($data && count($data) > 0) ? (($current_page - 1) * $per_page) + 1 : 0 }} - {{ ($current_page - 1) * $per_page + count($data) }}</span> arası gösteriliyor.
        </span>
        @php
            $paginatorButtonCount = 2;
            $start = max(1, $current_page - $paginatorButtonCount);
            $end = $start + $paginatorButtonCount * 2;
            if ($end >= $last_page) {
                $end = $last_page;
                $start = $end - $paginatorButtonCount * 2;
                $start = max(1, $start);
            }
        @endphp
        <ul class="inline-flex -space-x-px rtl:space-x-reverse text-sm h-8">
            <li>
                <button wire:click="setPage(1)" class="pagination-btn prev">
                    &lt;&lt;
                </button>
            </li>
            <li>
                <button wire:click="setPage({{ max(1, $current_page - 1) }})"
                        class="pagination-btn {{$current_page == 1 ? "disable" : ""}}">
                    &lt;
                </button>
            </li>
            @for($page = $start; $page <= $end; $page++)
                <li>
                    <button wire:click="setPage({{$page}})"
                            class="pagination-btn {{$current_page == $page ? "active" : ""}}">
                        {{ $page }}
                    </button>
                </li>
            @endfor
            <li>
                <button wire:click="setPage({{ min($last_page, $current_page + 1) }})"
                        class="pagination-btn {{$current_page == $last_page ? "disable" : ""}}">
                    &gt;
                </button>
            </li>
            <li>
                <button wire:click="setPage({{$last_page}})" class="pagination-btn next">
                    &gt;&gt;
                </button>
            </li>
        </ul>
    </nav>
</div>
