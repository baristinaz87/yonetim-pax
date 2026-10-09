@php use Carbon\Carbon; @endphp
<div>
    {{-- Mağazaya ait hiç hediye kontör hareketi yoksa bölüm hiç gösterilmez. --}}
    @if ($total_records > 0)
        <div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
            <div class="mx-2 my-2 text-xl font-bold">Hediye Kontör Yükleme Geçmişi</div>
            <div class="mx-2 mb-4 text-xs text-gray-500">
                Hediye kontör her dönem (ay) başında aylık miktara set edilir, mevcut bakiyenin üzerine eklenmez.
            </div>
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Yükleme Tarihi</th>
                    <th scope="col" class="px-6 py-3">Dönem</th>
                    <th scope="col" class="px-6 py-3">Önceki Hediye Kontör</th>
                    <th scope="col" class="px-6 py-3">Yüklenen</th>
                    <th scope="col" class="px-6 py-3">Yeni Hediye Kontör</th>
                    <th scope="col" class="px-6 py-3">İşlem</th>
                    <th scope="col" class="px-6 py-3">Kaynak</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data as $topup)
                    @php
                        $amount = (int) ($topup["amount"] ?? 0);
                        $isClear = ($topup["type"] ?? null) === "clear";
                        $sourceLabels = [
                            "cron"   => "Zamanlanmış görev",
                            "sync"   => "Bakiye senkronu",
                            "deduct" => "Harcama anında",
                        ];
                        $amountClass = $amount < 0 ? "text-red-600" : ($amount > 0 ? "text-green-700" : "text-gray-500");
                    @endphp
                    <tr wire:key="gift-topup-{{ $topup["id"] }}" class="bg-white border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{-- created_at efatura tarafında UTC tutulur; Türkiye saatiyle gösterilir --}}
                            {{ !empty($topup["created_at"]) ? Carbon::parse($topup["created_at"])->timezone('Europe/Istanbul')->format('d/m/Y H:i') : "" }}
                        </td>
                        <td class="px-6 py-4">
                            {{ !empty($topup["period"]) ? Carbon::parse($topup["period"])->format('m/Y') : "-" }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $topup["previous_gift_credit"] ?? 0 }}
                        </td>
                        <td class="px-6 py-4 font-medium {{ $amountClass }}">
                            {{ $amount > 0 ? "+" : "" }}{{ $amount }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $topup["new_gift_credit"] ?? 0 }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($isClear)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Plan dışı sıfırlama</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aylık yükleme</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            {{ $sourceLabels[$topup["source"] ?? ""] ?? ($topup["source"] ?? "-") }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{-- PAGINATION --}}
            <nav class="flex items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
                <span class="text-sm font-normal text-gray-500 mb-4 md:mb-0 block w-full md:inline md:w-auto">
                    <span class="font-semibold text-gray-900">{{ $total_records }}</span> kayıttan <span
                        class="font-semibold text-gray-900">{{ count($data) > 0 ? (($current_page - 1) * $per_page) + 1 : 0 }} - {{ ($current_page - 1) * $per_page + count($data) }}</span> arası gösteriliyor.
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
    @endif
</div>
