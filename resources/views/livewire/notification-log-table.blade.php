@php use Carbon\Carbon; @endphp
<div class="p-6 bg-white shadow-sm sm:rounded-lg m-6">
    <div class="mx-2 my-2 text-xl font-bold">İletişim Kayıtları</div>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">Tarih</th>
            <th scope="col" class="px-6 py-3">Mağaza</th>
            <th scope="col" class="px-6 py-3">Gönderim Tipi</th>
            <th scope="col" class="px-6 py-3">Gönderilen Adres</th>
            <th scope="col" class="px-6 py-3">Şablon Numarası</th>
            <th scope="col" class="px-6 py-3">İçerik</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $notification)
            <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                <td class="px-6 py-4">
                    {{ !empty($notification["created_at"]) ? Carbon::parse($notification["created_at"])->format('d/m/Y') : "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $notification["myshopify_domain"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $notification["type"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $notification["addresses"] ?? "" }}
                </td>
                <td class="px-6 py-4">
                    {{ $notification["template_id"] ?? "-" }}
                </td>
                <td class="px-6 py-4">
                    <div class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-sm">
                        @foreach($notification["payload"] as $key => $value)
                            <div class="text-slate-500">{{ $key }}</div>
                            <div class="text-slate-800">{{ $value }}</div>
                        @endforeach
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
