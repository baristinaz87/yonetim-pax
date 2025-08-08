@php use Carbon\Carbon; @endphp
<div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
    <div class="mx-2 my-2 text-xl font-bold">Ödemeler</div>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">Tarih</th>
            <th scope="col" class="px-6 py-3">Tutar (USD)</th>
            <th scope="col" class="px-6 py-3">Açıklama</th>
            <th scope="col" class="px-6 py-3">Charge ID</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $charge)
            <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                <td class="px-6 py-4">
                    {{ !empty($charge["created_at"]) ? Carbon::parse($charge["created_at"])->format('d/m/Y') : "" }}
                </td>
                <td class="px-6 py-4">
                    {{ !empty($charge["amount"]) ? "$".number_format((float)$charge["amount"], 2, ',', '') : "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $charge["description"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $charge["charge_id"] ?? "" }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{-- PAGINATION --}}
    <nav class="flex items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
        <span class="text-sm font-normal text-gray-500 mb-4 md:mb-0 block w-full md:inline md:w-auto">
            <span class="font-semibold text-gray-900">{{ $total_records }}</span> kayıttan <span
                    class="font-semibold text-gray-900">{{ (($current_page - 1) * $per_page) + 1 }} - {{ $current_page * $per_page }}</span> arası gösteriliyor.
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
                    <<
                </button>
            </li>
            <li>
                <button wire:click="setPage({{ max(1, $current_page - 1) }})"
                        class="pagination-btn {{$current_page == 1 ? "disable" : ""}}">
                    <
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
                    >
                </button>
            </li>
            <li>
                <button wire:click="setPage({{$last_page}})" class="pagination-btn next">
                    >>
                </button>
            </li>
        </ul>
    </nav>
</div>

