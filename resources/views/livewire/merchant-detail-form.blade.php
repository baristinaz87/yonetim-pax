<div>
    <div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
        <!-- Üst Başlık ve Butonlar -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">{{ $data["setting"]["unvan"] }}</h1>
            <div class="flex items-center gap-2">
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 flex items-center gap-1">
                    Shopify Partners
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
                <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">+ Kontör Yükle</button>
            </div>
        </div>
        <!-- Bilgi Kartı -->
        <div class="bg-gray-50 p-6 rounded-lg shadow-sm border">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                <!-- Sol Sütun -->
                <div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Mağaza Adı</p>
                        <p>{{ $data["setting"]["shop_name"] }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Mağaza Domain</p>
                        <a href="https://{{ $data["setting"]["shop_domain"] }}" target="_blank" class="text-blue-600 underline">{{ $data["setting"]["shop_domain"] }}</a>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600">Mağaza Açılış Tarihi</p>
                        <span class="inline-block bg-green-100 text-green-700 text-sm px-3 py-1 rounded mt-1">
                            {{ $data["setting"]["shop_created_at"] }}
                        </span>
                    </div>
                </div>
                <!-- Sağ Sütun -->
                <div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Mağaza Email</p>
                        <p>{{ $data["setting"]["shop_email"] }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Myshopify Domain</p>
                        <a href="https://{{ $data["setting"]["shop_myshopify_domain"] }}" target="_blank" class="text-blue-600 underline">
                            {{ $data["setting"]["shop_myshopify_domain"] }}
                        </a>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600">Shopify Paketi</p>
                        <p>{{ $data["setting"]["shop_plan"] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
        <form wire:submit.prevent="updateSetting">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Firma Ünvanı -->
                <div>
                    <label for="unvan" class="block text-sm font-medium text-gray-700 mb-1">
                        Firma Ünvanı <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="unvan"
                        wire:model.defer="unvan"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                        value="{{ $data["setting"]["unvan"] }}"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model.defer="email"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Telefon 1 -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Telefon 1
                    </label>
                    <input
                        type="text"
                        id="phone"
                        wire:model.defer="phone"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Telefon 2 -->
                <div>
                    <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                        Telefon 2 (Mobil)
                    </label>
                    <input
                        type="text"
                        id="mobile"
                        wire:model.defer="mobile"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Vergi Dairesi -->
                <div>
                    <label for="tax_office" class="block text-sm font-medium text-gray-700 mb-1">
                        Vergi Dairesi
                    </label>
                    <input
                        type="text"
                        id="tax_office"
                        wire:model.defer="tax_office"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Vergi Numarası / TCKN -->
                <div>
                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Vergi Numarası / TCKN
                    </label>
                    <input
                        type="text"
                        id="tax_number"
                        wire:model.defer="tax_number"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Varsayılan Vergi Oranı -->
                <div>
                    <label for="default_tax" class="block text-sm font-medium text-gray-700 mb-1">
                        Varsayılan Vergi Oranı
                    </label>
                    <?php $taxValues = [1, 10, 20]; ?>
                    <select id="default_tax" wire:model.defer="default_tax" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        @foreach($taxValues as $taxValue)
                            <option value="{{$taxValue}}" {{ $data["setting"]["default_tax"] === $taxValue ? "selected" : "" }}>%{{$taxValue}}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Vergi Seçimi -->
                <div>
                    <label for="tax_override" class="block text-sm font-medium text-gray-700 mb-1">
                        Vergi Seçimi
                    </label>
                    <select
                        id="tax_override"
                        wire:model.defer="tax_override"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    >
                        <option value="0" {{ !$data["setting"]["tax_override"] ? "selected" : "" }}>Shopify</option>
                        <option value="1" {{ $data["setting"]["tax_override"] ? "selected" : "" }}>Varsayılan KDV</option>
                    </select>
                </div>

                <!-- Aktif (Confirm) -->
                <div>
                    <label for="confirm" class="block text-sm font-medium text-gray-700 mb-1">
                        Aktif (Confirm)
                    </label>
                    <select
                        id="confirm"
                        wire:model.defer="confirm"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    >
                        <option value="1" {{ $data["setting"]["confirm"] ? "selected" : "" }}>Evet</option>
                        <option value="0" {{ !$data["setting"]["confirm"] ? "selected" : "" }}>Hayır</option>
                    </select>
                </div>

                <!-- İlk Kontör -->
                <div>
                    <label for="first_credit" class="block text-sm font-medium text-gray-700 mb-1">
                        İlk Kontör
                    </label>
                    <select
                        id="first_credit"
                        wire:model.defer="first_credit"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    >
                        <option value="1" {{ $data["setting"]["first_credit"] ? "selected" : "" }}>Evet</option>
                        <option value="0" {{ !$data["setting"]["first_credit"] ? "selected" : "" }}>Hayır</option>
                    </select>
                </div>

                <!-- API USER -->
                <div>
                    <label for="api_user" class="block text-sm font-medium text-gray-700 mb-1">
                        Api User
                    </label>
                    <input
                        type="text"
                        id="api_user"
                        wire:model.defer="api_user"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- API PASS -->
                <div>
                    <label for="api_pass" class="block text-sm font-medium text-gray-700 mb-1">
                        Api Pass
                    </label>
                    <input
                        type="text"
                        id="api_pass"
                        wire:model.defer="api_pass"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- XLST E-Fatura -->
                <div>
                    <label for="xslt_code_efatura" class="block text-sm font-medium text-gray-700 mb-1">
                        XLST E-Fatura
                    </label>
                    <input
                        type="text"
                        id="xslt_code_efatura"
                        wire:model.defer="xslt_code_efatura"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- XLST E-Arşiv -->
                <div>
                    <label for="xslt_code" class="block text-sm font-medium text-gray-700 mb-1">
                        XLST E-Arşiv
                    </label>
                    <input
                        type="text"
                        id="xslt_code"
                        wire:model.defer="xslt_code"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Otomatik Fatura -->
                <div>
                    <label for="auto_send" class="block text-sm font-medium text-gray-700 mb-1">
                        Otomatik Fatura
                    </label>
                    <select
                        id="auto_send"
                        wire:model.defer="auto_send"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    >
                        <option value="1" {{ $data["setting"]["auto_send"] ? "selected" : "" }}>Evet</option>
                        <option value="0" {{ !$data["setting"]["auto_send"] ? "selected" : "" }}>Hayır</option>
                    </select>
                </div>

                <!-- E-Mail Fatura Gönderimi -->
                <div>
                    <label for="send_email" class="block text-sm font-medium text-gray-700 mb-1">
                        E-Mail Fatura Gönderimi
                    </label>
                    <select
                        id="send_email"
                        wire:model.defer="send_email"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    >
                        <option value="1" {{ $data["setting"]["send_email"] ? "selected" : "" }}>Evet</option>
                        <option value="0" {{ !$data["setting"]["send_email"] ? "selected" : "" }}>Hayır</option>
                    </select>
                </div>

                <!-- Durum -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Durum
                    </label>
                    <?php
                        $statuses = [
                            "new"               => "Yeni Müşteriler",
                            "active"            => "Aktif Müşteriler",
                            "passive"           => "Pasif Müşteriler",
                            "on_track"          => "Takipteki Müşteriler",
                            "wait_return"       => "Dönüş Beklenenler",
                            "wait_activation"   => "Akt. Bekleyenler",
                            "wait_deactivation" => "Deakt. Bekleyenler"
                        ];
                    ?>
                    <select id="status" wire:model.defer="status" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        @foreach($statuses as $key => $value)
                            <option value="{{$key}}" {{ $data["setting"]["status"] === $key ? "selected" : "" }}>{{$value}}</option>
                        @endforeach
                    </select>
                </div>
                <div></div>
                <div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded">
                        Kaydet
                    </button>
                    <button type="reset" class="bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded border border-gray-300">
                        Vazgeç
                    </button>
                </div>
            </div>
            @if (session()->has('message'))
                <div class="mt-2 mb-4 px-4 py-2 bg-green-100 text-green-800 rounded">
                    {{ session('message') }}
                </div>
            @endif
        </form>
    </div>
</div>
