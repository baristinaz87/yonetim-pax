<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-4 flex justify-between items-center">
        <div class="flex gap-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Hizmet ara...">
            </div>
            <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="">Tüm Durumlar</option>
                <option value="active">Aktif</option>
                <option value="inactive">Pasif</option>
            </select>
        </div>
        <a href="{{ route('our-services.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Hizmet Ekle
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        <button wire:click="sortBy('title')" class="flex items-center">
                            Başlık
                            @if($sortField === 'title')
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    @if($sortDirection === 'asc')
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3">Açıklama</th>
                    <th scope="col" class="px-6 py-3">Link</th>
                    <th scope="col" class="px-6 py-3">
                        <button wire:click="sortBy('status')" class="flex items-center">
                            Durum
                            @if($sortField === 'status')
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    @if($sortDirection === 'asc')
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <button wire:click="sortBy('created_at')" class="flex items-center">
                            Oluşturma Tarihi
                            @if($sortField === 'created_at')
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    @if($sortDirection === 'asc')
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-6 py-3">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $service)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ $service->title }}
                        </th>
                        <td class="px-6 py-4">
                            <div class="max-w-xs truncate" title="{{ $service->description }}">
                                {{ $service->description }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($service->link)
                                <a href="{{ $service->link }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ Str::limit($service->link, 30) }}
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $service->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $service->status === 'active' ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $service->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('our-services.edit', $service->id) }}" class="text-blue-600 hover:underline">
                                    Düzenle
                                </a>
                                <button 
                                    wire:click="deleteService({{ $service->id }})" 
                                    wire:confirm="Bu hizmeti silmek istediğinizden emin misiniz?"
                                    class="text-red-600 hover:underline">
                                    Sil
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Henüz hiç hizmet eklenmemiş.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $services->links() }}
    </div>
</div>
