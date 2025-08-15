<?php

namespace App\Livewire;

use App\Models\OurService;
use Illuminate\View\View;
use Livewire\Component;

class OurServiceForm extends Component
{
    public ?OurService $service = null;
    public string $title = '';
    public string $description = '';
    public string $link = '';
    public string $status = 'active';

    public bool $isEditing = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'link' => 'nullable|string|max:255',
        'status' => 'required|in:active,inactive',
    ];

    public function mount(?int $serviceId = null): void
    {
        if ($serviceId) {
            $this->service = OurService::findOrFail($serviceId);
            $this->isEditing = true;
            $this->title = $this->service->title;
            $this->description = $this->service->description;
            $this->link = $this->service->link ?? '';
            $this->status = $this->service->status;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link ?: null,
            'status' => $this->status,
        ];

        if ($this->isEditing) {
            $this->service->update($data);
            session()->flash('message', 'Hizmet başarıyla güncellendi.');
        } else {
            OurService::create($data);
            session()->flash('message', 'Hizmet başarıyla eklendi.');
        }

        return redirect()->route('our-services');
    }

    public function render(): View
    {
        return view('livewire.our-service-form');
    }
}
