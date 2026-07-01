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
        "wait_deactivation" => "Deakt. Bekleyenler",
        "credit_expiring"   => "Kontör Tarihi Yaklaşan",
        "credit_expired"    => "Kontörü Bitenler",
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
        $tmp["merchant_id"] = $item["id"];
        $tmp["app_updated_at"] = $item["password_updated_at"];
        return $tmp;
    }, $data);

    // Group merchants by trimmed VKN (tax_number) so we can show how many
    // stores share the same tax id and list every related store on click.
    $taxNumberGroups = [];
    foreach ($merchants as $m) {
        $vkn = isset($m["tax_number"]) ? trim((string) $m["tax_number"]) : "";
        if ($vkn === "") {
            continue;
        }
        if (!isset($taxNumberGroups[$vkn])) {
            $taxNumberGroups[$vkn] = [
                "count"    => 0,
                "merchants" => [],
            ];
        }
        $taxNumberGroups[$vkn]["count"]++;
        $taxNumberGroups[$vkn]["merchants"][] = [
            "merchant_id" => $m["merchant_id"] ?? null,
            "domain"      => trim((string) ($m["shop_domain"] ?? "")),
            "unvan"       => trim((string) ($m["unvan"] ?? "")),
        ];
    }

    $isFilterButtonHide = empty($selectedStatus) && empty($unvanSearch) && empty($shopDomainSearch) && empty($sortField) && !$multiStoreOnly;
@endphp
<div class="overflow-x-auto bg-white rounded-lg shadow-sm pb-6">
    @if(!$isFilterButtonHide)
        <button wire:click="resetFilters()" class="bg-blue-500 text-white px-2 py-3 mb-4 rounded hover:bg-blue-600">
            Filtreleri Sıfırla
        </button>
    @endif
    @if($multiStoreOnly)
        <div class="mb-3 text-sm text-gray-600">
            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 font-medium">
                Çoklu Mağaza filtresi aktif
            </span>
            <span class="ml-2">Aynı VKN'ye sahip birden fazla mağazası olan kayıtlar, mağaza sayısına göre azalan sırada listelenir.</span>
        </div>
    @endif
    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center {{ $sortField == "setting_created_at" ? "active" : "" }}">
                    Kayıt Tarihi
                    <div wire:click="setSort('setting_created_at')" class="px-2 cursor-pointer">
                        <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"></path>
                        </svg>
                    </div>
                </div>
            </th>
            <th scope="col" class="px-6 py-3">Firma</th>
            <th scope="col" class="px-6 py-3">Shopify</th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center {{ $sortField == "password_updated_at" ? "active" : "" }}">
                    Uygulama Güncelleme Tarihi
                    <div wire:click="setSort('password_updated_at')" class="px-2 cursor-pointer">
                        <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"></path>
                        </svg>
                    </div>
                </div>
            </th>
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
            <th scope="col" class="px-6 py-3 min-w-[200px]">Durum</th>
            <th scope="col" class="px-6 py-3">İşlemler</th>
        </tr>
        <tr>
            <th></th>
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
            <th></th>
            <th>
                <select id="status" wire:model="selectedStatus" wire:change="$set('selectedStatus', $event.target.value)" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
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
                <th scope="row" class="px-6 py-4 font-medium text-gray-900">
                    {{ !empty($merchant['created_at']) ? Carbon::parse($merchant['created_at'])->format('d/m/Y') : '' }}
                </th>
                <th scope="row" class="px-6 py-4 font-medium text-gray-900 break-words">
                    @php
                        $unvan = $merchant['unvan'] ? trim($merchant['unvan']) : '';
                        $currentVkn = isset($merchant['tax_number']) ? trim((string) $merchant['tax_number']) : '';
                        $vknGroup = ($currentVkn !== '' && isset($taxNumberGroups[$currentVkn]))
                            ? $taxNumberGroups[$currentVkn]
                            : null;
                    @endphp
                    <div class="flex items-center gap-2 flex-wrap">
                        <span>{{ $unvan }}</span>
                        @if($vknGroup && $vknGroup['count'] > 1)
                            <div
                                x-data="{ open: false }"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                                class="relative inline-flex"
                            >
                                <button
                                    type="button"
                                    wire:key="vkn-badge-{{ $merchant['merchant_id'] }}"
                                    @click.stop="open = !open"
                                    class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
                                    aria-label="Aynı VKN'ye ait mağazaları göster"
                                >
                                    ({{ $vknGroup['count'] }})
                                </button>
                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-1"
                                    class="absolute z-50 left-0 top-full mt-2 w-72 max-w-xs"
                                    style="display: none;"
                                >
                                    <div class="bg-gray-900 text-white text-xs rounded-lg py-2 px-3 shadow-lg">
                                        <div class="font-semibold mb-1">Aynı VKN'ye ait mağazalar ({{ $vknGroup['count'] }}):</div>
                                        <ul class="space-y-0.5">
                                            @foreach($vknGroup['merchants'] as $related)
                                                @php
                                                    $relatedDomain = $related['domain'] !== '' ? $related['domain'] : '(domain yok)';
                                                    $relatedUrl = !empty($related['merchant_id'])
                                                        ? route('merchant-detail', ['id' => $related['merchant_id']])
                                                        : null;
                                                @endphp
                                                <li class="truncate" title="{{ $relatedDomain }}">
                                                    @if($relatedUrl)
                                                        <a href="{{ $relatedUrl }}" target="_blank" class="text-blue-200 hover:text-white hover:underline">
                                                            {{ $relatedDomain }} (ID : {{ $related['merchant_id'] }})
                                                        </a>
                                                    @else
                                                        {{ $relatedDomain }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="w-2 h-2 bg-gray-900 rotate-45 -mt-1 ml-3"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </th>
                <td class="px-6 py-4">
                    {{ $merchant['shop_domain'] ?? '' }}
                </td>
                <td class="px-6 py-4">
                    {{ !empty($merchant['app_updated_at']) ? Carbon::parse($merchant['app_updated_at'])->format('d/m/Y') : '' }}
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
                    <a target="_blank" href="{{ route('merchant-detail', ['id' => $merchant['merchant_id']]) }}" class="font-medium text-blue-600 hover:underline">Düzenle</a>
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
        <div class="flex items-center gap-2">
            <label for="perPage" class="text-sm text-gray-500">Sayfa başına:</label>
            <select id="perPage" wire:model="perPage" wire:change="$set('perPage', $event.target.value)"
                    class="min-w-[75px] border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring focus:ring-blue-200">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="100000">Tümü</option>
            </select>
        </div>
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
