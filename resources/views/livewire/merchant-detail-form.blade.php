@php
    $statuses = [
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
@endphp

<div>
    @if (session()->has('addCreditMessage'))
        <div class="m-5 px-4 py-2 bg-green-100 text-green-800 rounded relative">
            {{ session('addCreditMessage') }}
            <button wire:click="clearMessageSession('addCreditMessage')" type="button" class="absolute right-4 top-2 text-green-800/70 hover:text-green-900" aria-label="Kapat" title="Kapat">
                X
            </button>
        </div>
    @endif
    @if ($data["app_status"] != "active")
        <div class="my-2 px-4 py-2 bg-red-200 rounded text-red-600 text-sm">Uygulama Silinmiş</div>
    @endif
    <div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
        <!-- Üst Başlık ve Butonlar -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">{{ $unvan }}</h1>
            <div class="flex items-center gap-2">
                <a href="https://partners.shopify.com/1779760/stores/{{ $shop_id }}" target="_blank" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 flex items-center gap-1">
                    Shopify Partners
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
                <button wire:click="openAddCreditModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">+ Kontör Yükle</button>
                <span class="{{ $colors[$status ?? ""] ?? "bg-gray-300" }} text-white px-4 py-2 rounded">
                     {{ $statuses[$status ?? ""] ?? "Durum Boş" }}
                </span>
            </div>
            <div wire:ignore id="add-credit-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-2xl max-h-full">
                    <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold">E-Fatura Kontör Yükleme</h3>
                            <button wire:click="closeAddCreditModal()" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Kapat</span>
                            </button>
                        </div>
                        {{-- ADD CREDIT FORM --}}
                        <form class="px-6 py-5 space-y-5" wire:submit.prevent="addCredit">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Mağaza <span class="text-red-600">*</span></label>
                                <input disabled type="text" placeholder="ÖR: abc123.myshopify.com"
                                       class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                       wire:model.defer="creditFormData.shop_name" required>
                                @error('creditFormData.shop_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Kontör Adedi <span class="text-red-600">*</span></label>
                                <input type="number" min="1" step="1" placeholder="ÖR: 500"
                                       class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                                       wire:model.defer="creditFormData.credit" required>
                                @error('creditFormData.credit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Tutar (USD) <span class="text-red-600">*</span></label>
                                <input type="number" min="0" step="0.01" placeholder="ÖR: 50"
                                       class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                                       wire:model.defer="creditFormData.amount" required>
                                @error('creditFormData.amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Açıklama <span class="text-red-600">*</span></label>
                                <textarea rows="6" placeholder="ÖR: 500 Kontör Bedeli"
                                          class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                                          wire:model.defer="creditFormData.description" required></textarea>
                                @error('creditFormData.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="block bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded">
                                Yükle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Bilgi Kartı -->
        <div class="bg-gray-50 p-6 rounded-lg shadow-sm border">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                <!-- Sol Sütun -->
                <div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Mağaza Adı</p>
                        <p>{{ $shop_name ?? "" }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Mağaza Domain</p>
                        @if($shop_domain ?? false)
                            <a href="https://{{ $shop_domain }}" target="_blank" class="text-blue-600 underline">{{ $shop_domain }}</a>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-600">Mağaza Açılış Tarihi</p>
                        <span class="inline-block bg-green-100 text-green-700 text-sm px-3 py-1 rounded mt-1">
                            {{ $shop_created_at ?? "" }}
                        </span>
                    </div>
                </div>
                <!-- Sağ Sütun -->
                <div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Mağaza Email</p>
                        <p>{{ $shop_email ?? "" }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="font-medium text-gray-600">Myshopify Domain</p>
                        @if($shop_myshopify_domain ?? false)
                            <a href="https://{{ $shop_myshopify_domain }}" target="_blank" class="text-blue-600 underline">{{ $shop_myshopify_domain }}</a>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-600">Shopify Paketi</p>
                        <p>{{ $shop_plan ?? "" }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
        <form wire:submit.prevent="updateCreditFields">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="credit" class="block text-sm font-medium text-gray-700 mb-1">
                        Kalan Kontör
                    </label>
                    <input
                        disabled
                        type="text"
                        id="credit"
                        wire:model.defer="data.credit"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>
                <div>
                    <label for="last_top_up_at" class="block text-sm font-medium text-gray-700 mb-1">
                        Son Kontör Yükleme Tarihi
                    </label>
                    <input
                        disabled
                        type="date"
                        id="last_top_up_at"
                        wire:model.defer="data.last_top_up_at"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>
                <div>
                    <label for="credit_expired_at" class="block text-sm font-medium text-gray-700 mb-1">
                        Kontör Bitiş Tarihi
                    </label>
                    <input
                        type="date"
                        id="credit_expired_at"
                        wire:model.defer="data.credit_expired_at"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>
                <div>
                    <label for="credit_tracking_at" class="block text-sm font-medium text-gray-700 mb-1">
                        Takip Tarihi
                    </label>
                    <input
                        type="date"
                        id="credit_tracking_at"
                        wire:model.defer="data.credit_tracking_at"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>
                <div>
                    <label for="app_status" class="block text-sm font-medium text-gray-700 mb-1">
                        Uygulama Durumu
                    </label>
                    <select disabled id="app_status" wire:model.defer="data.app_status" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="active">Aktif</option>
                        <option value="passive">Uygulama Silinmiş</option>
                    </select>
                </div>
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                        User ID
                    </label>
                    <input
                        disabled
                        type="text"
                        id="user_id"
                        wire:model.defer="data.user_id"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>
                <div>
                    <label for="app_updated_at" class="block text-sm font-medium text-gray-700 mb-1">
                        Uygulama Güncelleme Tarihi
                    </label>
                    <input
                        disabled
                        type="date"
                        id="app_updated_at"
                        wire:model.defer="data.app_updated_at"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>
                <div>
                    <label for="created_at" class="block text-sm font-medium text-gray-700 mb-1">
                        Kayıt Tarihi
                    </label>
                    <input
                        disabled
                        type="date"
                        id="created_at"
                        wire:model.defer="data.created_at"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>
            </div>
            <div class="mt-2">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded">
                    Kaydet
                </button>
                <button type="button" wire:click="resetForm()" class="bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded border border-gray-300">
                    Vazgeç
                </button>
            </div>
            @if (session()->has('updateCreditFieldsMessage'))
                <div class="mt-2 mb-4 px-4 py-2 bg-green-100 text-green-800 rounded relative">
                    {{ session('updateCreditFieldsMessage') }}
                    <button wire:click="clearMessageSession('updateCreditFieldsMessage')" type="button" class="absolute right-4 top-2 text-green-800/70 hover:text-green-900" aria-label="Kapat" title="Kapat">
                        X
                    </button>
                </div>
            @endif
        </form>
        <div class="flex justify-between mt-6">
            <div>
                <span class="text-2xl font-bold underline">Kontör Hatırlatma Bildirimi</span>
            </div>
            <div>
                <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">SMS GÖNDER</button>
                <button class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">EMAIL GÖNDER</button>
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
                        wire:model.defer="data.unvan"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
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
                        wire:model.defer="data.email"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Telefon 1 -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Telefon 1 <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="phone"
                        wire:model.defer="data.phone"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Telefon 2 -->
                <div>
                    <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                        Telefon 2 (Mobil) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="mobile"
                        wire:model.defer="data.mobile"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Vergi Dairesi -->
                <div>
                    <label for="tax_office" class="block text-sm font-medium text-gray-700 mb-1">
                        Vergi Dairesi <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="tax_office"
                        wire:model.defer="data.tax_office"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Vergi Numarası / TCKN -->
                <div>
                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Vergi Numarası / TCKN <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="tax_number"
                        wire:model.defer="data.tax_number"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Varsayılan Vergi Oranı -->
                <div>
                    <label for="default_tax" class="block text-sm font-medium text-gray-700 mb-1">
                        Varsayılan Vergi Oranı <span class="text-red-500">*</span>
                    </label>

                    <select id="default_tax" wire:model.defer="data.default_tax" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="1">%1</option>
                        <option value="10">%10</option>
                        <option value="20">%20</option>
                    </select>
                </div>

                <!-- Vergi Seçimi -->
                <div>
                    <label for="tax_override" class="block text-sm font-medium text-gray-700 mb-1">
                        Vergi Seçimi <span class="text-red-500">*</span>
                    </label>
                    <select id="tax_override" wire:model.defer="data.tax_override" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="0">Shopify</option>
                        <option value="1">Varsayılan KDV</option>
                    </select>
                </div>

                <!-- Aktif (Confirm) -->
                <div>
                    <label for="confirm" class="block text-sm font-medium text-gray-700 mb-1">
                        Aktif (Confirm) <span class="text-red-500">*</span>
                    </label>
                    <select id="confirm" wire:model.defer="data.confirm" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="1">Evet</option>
                        <option value="0">Hayır</option>
                    </select>
                </div>

                <!-- İlk Kontör -->
                <div>
                    <label for="first_credit" class="block text-sm font-medium text-gray-700 mb-1">
                        İlk Kontör <span class="text-red-500">*</span>
                    </label>
                    <select id="first_credit" wire:model.defer="data.first_credit" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="1">Evet</option>
                        <option value="0">Hayır</option>
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
                        wire:model.defer="data.api_user"
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
                        wire:model.defer="data.api_pass"
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
                        wire:model.defer="data.xslt_code_efatura"
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
                        wire:model.defer="data.xslt_code"
                        class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                    />
                </div>

                <!-- Otomatik Fatura -->
                <div>
                    <label for="auto_send" class="block text-sm font-medium text-gray-700 mb-1">
                        Otomatik Fatura <span class="text-red-500">*</span>
                    </label>
                    <select id="auto_send" wire:model.defer="data.auto_send" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="1">Evet</option>
                        <option value="0">Hayır</option>
                    </select>
                </div>

                <!-- E-Mail Fatura Gönderimi -->
                <div>
                    <label for="send_email" class="block text-sm font-medium text-gray-700 mb-1">
                        E-Mail Fatura Gönderimi <span class="text-red-500">*</span>
                    </label>
                    <select id="send_email" wire:model.defer="data.send_email" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="1">Evet</option>
                        <option value="0">Hayır</option>
                    </select>
                </div>

                <!-- Durum -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Durum
                    </label>
                    <select id="status" wire:model.defer="data.status" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                        <option value="">-- Seçiniz --</option>
                        @foreach($statuses as $key => $value)
                            <option value="{{$key}}">{{$value}}</option>
                        @endforeach
                    </select>
                </div>
                <div></div>
                <div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded">
                        Kaydet
                    </button>
                    <button type="button" wire:click="resetForm()" class="bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded border border-gray-300">
                        Vazgeç
                    </button>
                </div>
            </div>
            @if (session()->has('updateSettingMessage'))
                <div class="mt-2 mb-4 px-4 py-2 bg-green-100 text-green-800 rounded relative">
                    {{ session('updateSettingMessage') }}
                    <button wire:click="clearMessageSession('updateSettingMessage')" type="button" class="absolute right-4 top-2 text-green-800/70 hover:text-green-900" aria-label="Kapat" title="Kapat">
                        X
                    </button>
                </div>
            @endif
            @php($all = array_keys($errors->get('data.*')))
            @foreach ($all as $key)
                @error($key) <div class="my-2 px-4 py-2 bg-red-200 rounded text-red-600 text-sm">{{ $message }}</div> @enderror
            @endforeach
        </form>
    </div>
    <script>
        (function () {
            let modalInstance = null;

            function getInstance() {
                const el = document.getElementById('add-credit-modal');
                if (!el) return null;

                const Ctor = window.Modal || (window.flowbite && window.flowbite.Modal);
                if (!Ctor) {
                    console.warn('Flowbite Modal constructor bulunamadı.');
                    return null;
                }
                modalInstance ||= new Ctor(el, {
                    placement: 'center',
                    backdrop: 'dynamic',
                    closable: true,
                });

                return modalInstance;
            }

            document.addEventListener('open-add-credit-modal', () => {
                getInstance()?.show();
            });

            document.addEventListener('close-add-credit-modal', () => {
                getInstance()?.hide();
            });
        })();
    </script>
</div>
