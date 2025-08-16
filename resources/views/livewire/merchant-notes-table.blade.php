@php use Carbon\Carbon; @endphp
<div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
    <div class="mx-2 my-2 text-xl font-bold">Notlar</div>
    <form wire:submit.prevent="createNote">
        <textarea
            id="noteText"
            wire:model.defer="description"
            rows="3"
            class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 mb-2"
            placeholder="Notunuzu yazın..."
        ></textarea>
        <div class="mb-4">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded">
                Kaydet
            </button>
            <button type="reset" class="bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded border border-gray-300">
                Vazgeç
            </button>
        </div>
    </form>
    @error("description") <div class="my-2 px-4 py-2 bg-red-200 rounded text-red-600 text-sm">{{ $message }}</div> @enderror
    @if (session()->has('message'))
        <div class="mt-2 mb-4 px-4 py-2 bg-green-100 text-green-800 rounded relative">
            {{ session('message') }}
            <button wire:click="clearMessageSession()" type="button" class="absolute right-4 top-2 text-green-800/70 hover:text-green-900" aria-label="Kapat" title="Kapat">
                X
            </button>
        </div>
    @endif
    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">Tarih</th>
            <th scope="col" class="px-6 py-3">Not</th>
            <th scope="col" class="px-6 py-3">İşlemler</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $note)
            <tr wire:key="note-{{ $note["id"] }}" class="bg-white border-b border-gray-200 hover:bg-gray-50">
                <td class="px-6 py-4">
                    {{ !empty($note["created_at"]) ? Carbon::parse($note["created_at"])->format('d/m/Y') : "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $note["description"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    <button type="button" wire:click="openDeleteModal({{ $note["id"] }})" class="font-medium text-red-600 hover:underline">
                        Sil
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{--  NOTE DELETE MODAL  --}}
    <div wire:ignore id="popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <button wire:click="closeDeleteModal()" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Kapat</span>
                </button>
                <div class="p-4 md:p-5 text-center">
                    <svg wire:click="closeDeleteModal()" class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</h3>
                    <button wire:click="removeNote()" type="button" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                        Evet, sil
                    </button>
                    <button wire:click="closeDeleteModal()" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Vazgeç</button>
                </div>
            </div>
        </div>
    </div>
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
    <script>
        (function () {
            let modalInstance = null;

            function getInstance() {
                const el = document.getElementById('popup-modal');
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

            document.addEventListener('open-delete-modal', () => {
                getInstance()?.show();
            });

            document.addEventListener('close-delete-modal', () => {
                getInstance()?.hide();
            });
        })();
    </script>

</div>
