@php
    use Carbon\Carbon;

    $statuses = [
        ""                  => "Durum Seçiniz",
        "new"               => "Yeni Müşteriler",
        "active"            => "Aktif Müşteriler",
        "passive"           => "Pasif Müşteriler",
        "on_track"          => "Takipteki Müşteriler",
        "wait_return"       => "Dönüş Beklenenler",
        "wait_activation"   => "Akt. Bekleyenler",
        "wait_deactivation" => "Deakt. Bekleyenler"
    ];

    $colors = [
        "new"               => "bg-[#2563EB]",
        "active"            => "bg-[#10B981]",
        "passive"           => "bg-[#9CA3AF]",
        "on_track"          => "bg-[#F59E0B]",
        "wait_return"       => "bg-[#F97316]",
        "wait_activation"   => "bg-[#3B82F6]",
        "wait_deactivation" => "bg-[#EF4444]",
    ];

    $merchants = array_map(function ($item) {
        $tmp = $item["setting"] ?? [];
        $tmp["merchantId"] = $item["id"];
        return $tmp;
    }, $data)
@endphp
<div>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">Firma</th>
            <th scope="col" class="px-6 py-3">Shopify</th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center {{ $sortField == "setting_credit_expired_at" ? "active" : "" }}">
                    Kontör Bitme Tarihi
                    <div wire:click="setSort('setting_credit_expired_at')" class="px-2 cursor-pointer">
                        <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"></path>
                        </svg>
                    </div>
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center {{ $sortField == "setting_credit" ? "active" : "" }}">
                    Kalan Kontör
                    <div wire:click="setSort('setting_credit')" class="px-2 cursor-pointer">
                        <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"></path>
                        </svg>
                    </div>
                </div>
            </th>
            <th scope="col" class="px-6 py-3">Durum</th>
            <th scope="col" class="px-6 py-3">İşlemler</th>
        </tr>
        <tr>
            <th scope="col" class="px-6 py-3">
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.500ms="unvanSearch" type="text" id="table-search" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Firma Ara">
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.500ms="shopDomainSearch" type="text" id="table-search" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Shopify Ara">
                </div>
            </th>
            <th></th>
            <th></th>
            <th>
                <select id="status" wire:change="$set('selectedStatus', $event.target.value)" class="min-w-[200px] block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                    @foreach($statuses as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($merchants as $merchant)
            <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                    {{ $merchant['unvan'] ?? '' }}
                </th>
                <td class="px-6 py-4">
                    {{ $merchant['shop_domain'] ?? '' }}
                </td>
                <td class="px-6 py-4">
                    {{ !empty($merchant['credit_expired_at']) ? Carbon::parse($merchant['credit_expired_at'])->format('d/m/Y') : '' }}
                </td>
                <td class="px-6 py-4">
                    {{ $merchant['credit'] ?? '' }}
                </td>
                <td class="px-6 py-4">
                    <span class="{{ $colors[$merchant["status"] ?? ""] ?? "bg-gray-300" }} text-white p-2 rounded">
                        {{ $statuses[$merchant["status"] ?? "empty"] ?? "Durum Boş" }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('merchant-detail', ['id' => $merchant['merchantId']]) }}" class="font-medium text-blue-600 hover:underline">Düzenle</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{-- PAGINATION --}}
    <nav class="flex items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
        <span class="text-sm font-normal text-gray-500 mb-4 md:mb-0 block w-full md:inline md:w-auto">
            <span class="font-semibold text-gray-900">{{ $total_records }}</span> kayıttan <span class="font-semibold text-gray-900">{{ (($current_page - 1) * $per_page) + 1 }} - {{ $current_page * $per_page }}</span> arası gösteriliyor.
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
                <button wire:click="setPage({{ max(1, $current_page - 1) }})" class="pagination-btn {{$current_page == 1 ? 'disable' : ''}}">
                    <
                </button>
            </li>
            @for($page = $start; $page <= $end; $page++)
                <li>
                    <button wire:click="setPage({{$page}})" class="pagination-btn {{$current_page == $page ? 'active' : ''}}">
                        {{ $page }}
                    </button>
                </li>
            @endfor
            <li>
                <button wire:click="setPage({{ min($last_page, $current_page + 1) }})" class="pagination-btn {{$current_page == $last_page ? 'disable' : ''}}">
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
