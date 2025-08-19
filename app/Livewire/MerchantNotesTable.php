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

    public ?int $selectedNoteId = null;

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

    public function openDeleteModal($id): void
    {
        $this->selectedNoteId = $id;
        $this->dispatch('open-delete-modal');
    }

    public function closeDeleteModal(): void
    {
        $this->selectedNoteId = null;
        $this->dispatch('close-delete-modal');
    }

    public function removeNote(): void
    {
        if ($this->selectedNoteId == null) return;
        $this->eFaturaClient->removeMerchantNote($this->selectedNoteId);
        $this->selectedNoteId = null;
        $this->dispatch('close-delete-modal');
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
