<?php

namespace App\Livewire;

use App\Models\OurService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;


class OurServiceForm extends Component
{
    use WithFileUploads;

    public ?OurService $service = null;
    public $image = null;
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
        'image' => 'nullable|image|max:1024|dimensions:min_width=250',
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
            // Image will be handled in the view
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

        if ($this->image) {
            // Delete old image if exists
            if ($this->isEditing && $this->service->image) {
                Storage::disk('public')->delete($this->service->image);
            }

            // Save original image
            $filename = 'services/' . time() . '_' . $this->image->getClientOriginalName();
            Storage::disk('public')->put($filename, file_get_contents($this->image->getRealPath()));

            $data['image'] = $filename;
        }

        if ($this->isEditing) {
            $this->service->update($data);
            session()->flash('message', 'Hizmet başarıyla güncellendi.');
        } else {
            OurService::create($data);
            session()->flash('message', 'Hizmet başarıyla eklendi.');
        }

        return redirect()->route('our-services');
    }

    public function removeImage()
    {
        if ($this->isEditing && $this->service->image) {
            Storage::disk('public')->delete($this->service->image);
            $this->service->update(['image' => null]);
            session()->flash('message', 'Resim başarıyla kaldırıldı.');
        }
    }

    public function render(): View
    {
        return view('livewire.our-service-form');
    }
}
