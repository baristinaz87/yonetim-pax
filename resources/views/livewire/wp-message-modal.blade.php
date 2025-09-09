<div>
    <button wire:click="openWpMessageModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">WP MESAJI GÖNDER</button>
    <div wire:ignore id="wp-message-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-2xl max-h-full">
                <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold">E-Fatura Whatsapp Mesaj Gönderimi</h3>
                        <button wire:click="closeWpMessageModal()" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Kapat</span>
                        </button>
                    </div>
                    <form class="px-6 py-5 space-y-5" wire:submit.prevent="sendMessage">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Mağaza <span class="text-red-600">*</span></label>
                            <input disabled type="text" placeholder="ÖR: abc123.myshopify.com"
                                   class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                   wire:model.defer="formData.shop_myshopify_domain" required>
                            @error('formData.shop_myshopify_domain') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Telefon Numaraları <span class="text-red-600">*</span></label>
                            <input disabled type="text" placeholder="ÖR: abc123.myshopify.com"
                                   class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                   wire:model.defer="formData.phones" required>
                            @error('formData.phones') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="template" class="block text-sm font-medium text-gray-700 mb-1">
                                Template Seçimi
                                <span class="text-red-600">*</span>
                            </label>
                            <select id="template" wire:model.defer="formData.template" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                                @foreach($templates as $key => $value)
                                    <option value="{{$key}}">{{$value}}</option>
                                @endforeach
                            </select>
                            @error('formData.template') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Template Harici Mesaj</label>
                            <textarea wire:model.defer="formData.message" rows="6" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"></textarea>
                            @error('formData.message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="block bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded">
                            Gönder
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <script>
        (function () {
            let modalInstance = null;

            function getInstance() {
                const el = document.getElementById('wp-message-modal');
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

            document.addEventListener('open-wp-message-modal', () => {
                getInstance()?.show();
            });

            document.addEventListener('close-wp-message-modal', () => {
                getInstance()?.hide();
            });
        })();
    </script>
</div>
