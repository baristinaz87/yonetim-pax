<div>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.500ms="unvanSearch" type="text" id="table-search" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Firma Ara">
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.500ms="shopDomainSearch" type="text" id="table-search" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Shopify Ara">
                </div>
            </th>
            <th></th>
            <th></th>
            <th>
                <div>
                    <button id="dropdownRadioButton" data-dropdown-toggle="dropdownRadio" class="inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700" type="button">
                        Durum Seç
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownRadio" class="z-10 hidden w-48 bg-white divide-y divide-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:divide-gray-600" data-popper-reference-hidden="" data-popper-escaped="" data-popper-placement="top" style="position: absolute; inset: auto auto 0px 0px; margin: 0px; transform: translate3d(522.5px, 3847.5px, 0px);">
                        <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownRadioButton">
                            @foreach($status as $key => $value)
                                <li>
                                    <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <input wire:click="$set('selectedStatus', '{{$value}}')" id="choice-{{$value}}" type="radio" value="{{$value}}" name="filter-radio" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="choice-{{$value}}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">{{$key}}</label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $customer)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    {{ $customer["setting"]["unvan"] ?? "" }}
                </th>
                <td class="px-6 py-4">
                    {{ $customer["setting"]["shop_domain"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $customer["setting"]["credit_expired_at"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $customer["setting"]["credit"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $customer["setting"]["status"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('merchant-detail', ['id' => $customer["id"]]) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Düzenle</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{-- PAGINATION --}}
    <nav class="flex items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400 mb-4 md:mb-0 block w-full md:inline md:w-auto">
            <span class="font-semibold text-gray-900 dark:text-white">{{ $total_records }}</span> kayıttan <span class="font-semibold text-gray-900 dark:text-white">{{ (($current_page - 1) * $per_page) + 1 }} - {{ $current_page * $per_page }}</span> arası gösteriliyor.
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
                <button wire:click="setPage({{ max(1, $current_page - 1) }})" class="pagination-btn {{$current_page == 1 ? "disable" : ""}}">
                    <
                </button>
            </li>
            @for($page = $start; $page <= $end; $page++)
                <li>
                    <button wire:click="setPage({{$page}})" class="pagination-btn {{$current_page == $page ? "active" : ""}}">
                        {{ $page }}
                    </button>
                </li>
            @endfor
            <li>
                <button wire:click="setPage({{ min($last_page, $current_page + 1) }})" class="pagination-btn {{$current_page == $last_page ? "disable" : ""}}">
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

