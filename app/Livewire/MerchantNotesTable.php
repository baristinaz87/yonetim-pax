<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantNotesTable extends Component
{
    public string $merchantId;

    private EFaturaClient $eFaturaClient;

    public int $page = 1;
    public int $perPage = 10;
    public int $total = 10;

    public string $description = "";

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function mount($id): void
    {
        $this->merchantId = $id;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function clearMessageSession(): void
    {
        session()->remove("message");
    }

    public function createNote(): void
    {
        $validated = $this->validate(["description" => "required|string"]);
        $validated["user_id"] = $this->merchantId;
        $this->eFaturaClient->createMerchantNote($validated);
        session()->flash('message', 'Not başarıyla oluşturuldu.');
        $this->resetExcept('merchantId');
    }

    public function removeNote($id): void
    {
        $this->eFaturaClient->removeMerchantNote($id);
        session()->flash('message', 'Not başarıyla silindi.');
    }

    public function render(): View
    {
        $data = $this->eFaturaClient->getMerchantNotes(
            $this->merchantId,
            $this->page,
            $this->perPage
        );

        $data["total_records"] = $data["total"];
        return view('livewire.merchant-notes-table', $data);
    }
}
