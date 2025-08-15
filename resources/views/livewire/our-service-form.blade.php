<div>
    <form wire:submit="save">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ $isEditing ? 'Hizmet Düzenle' : 'Yeni Hizmet Ekle' }}
                    </h3>
                </div>

                <div class="space-y-6">
                    <div>
                        <x-input-label for="title" :value="__('Başlık')" />
                        <x-text-input 
                            id="title" 
                            wire:model="title" 
                            type="text" 
                            class="mt-1 block w-full" 
                            required 
                        />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Açıklama')" />
                        <textarea 
                            id="description" 
                            wire:model="description" 
                            rows="4"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            required
                        ></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="link" :value="__('Link (İsteğe Bağlı)')" />
                        <x-text-input 
                            id="link" 
                            wire:model="link" 
                            type="url" 
                            class="mt-1 block w-full" 
                            placeholder="https://example.com"
                        />
                        <x-input-error :messages="$errors->get('link')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Durum')" />
                        <select 
                            id="status" 
                            wire:model="status" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('our-services') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        İptal
                    </a>
                    <x-primary-button>
                        {{ $isEditing ? 'Güncelle' : 'Kaydet' }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </form>
</div>
