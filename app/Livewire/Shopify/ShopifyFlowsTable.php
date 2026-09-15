<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Constant\ProviderTypeConstant;
use App\Models\EmailContent;
use App\Models\WpContent;
use App\Models\Shopify\App;
use App\Models\Shopify\EventGenerator;
use App\Models\Shopify\Flow;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shopify Akışlar')]
class ShopifyFlowsTable extends Component
{
    public array $form = [];
    public ?int $editingId = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name'                 => '',
            'event_type'           => 'installed',
            'app_ids'              => [],
            'channels'             => [ProviderTypeConstant::EMAIL_PROVIDER],
            'delay_minutes'        => 0,
            'whatsapp_template_id' => '',
            'email_template_id'    => '',
            'active'               => true,
        ];
    }

    public function updatedFormEventType(string $eventType): void
    {
        $this->form['app_ids'] = [];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.name'                 => ['required', 'string', 'max:255'],
            'form.event_type'           => ['required', Rule::in(array_keys($this->availableEventTypes()))],
            'form.app_ids'              => ['required', 'array', 'min:1'],
            'form.app_ids.*'            => ['integer', 'exists:shopify_apps,id'],
            'form.channels'             => ['required', 'array', 'min:1'],
            'form.channels.*'           => ['in:'.ProviderTypeConstant::WP_PROVIDER.','.ProviderTypeConstant::EMAIL_PROVIDER],
            'form.delay_minutes'        => ['required', 'integer', 'min:0', 'max:43200'],
            'form.whatsapp_template_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::exists('wp_contents', 'brevo_template_id')->where('status', true),
            ],
            'form.email_template_id'    => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('email_contents', 'id')->where('status', true),
            ],
            'form.active'               => ['boolean'],
        ])['form'];

        $appIds = array_values(array_unique(array_map('intval', $validated['app_ids'])));
        $allowedAppIds = $this->appIdsForEventType($validated['event_type']);
        if ($allowedAppIds !== null && array_diff($appIds, $allowedAppIds) !== []) {
            $this->addError('form.app_ids', 'Seçilen event oluşturucu için tanımlı uygulamalardan seçim yapmalısınız.');

            return;
        }

        $channels = array_values(array_unique($validated['channels']));

        if (in_array(ProviderTypeConstant::WP_PROVIDER, $channels, true) && empty($validated['whatsapp_template_id'])) {
            $this->addError('form.whatsapp_template_id', 'WhatsApp kanalı için template seçmelisiniz.');
            return;
        }

        if (in_array(ProviderTypeConstant::EMAIL_PROVIDER, $channels, true) && empty($validated['email_template_id'])) {
            $this->addError('form.email_template_id', 'Mail kanalı için template seçmelisiniz.');
            return;
        }

        $values = [
            'name'                 => $validated['name'],
            'event_type'           => $validated['event_type'],
            'app_ids'              => $appIds,
            'channels'             => $channels,
            'delay_minutes'        => (int) $validated['delay_minutes'],
            'whatsapp_template_id' => in_array(ProviderTypeConstant::WP_PROVIDER, $channels, true) ? $validated['whatsapp_template_id'] : null,
            'email_template_id'    => in_array(ProviderTypeConstant::EMAIL_PROVIDER, $channels, true) ? (int) $validated['email_template_id'] : null,
            'active'               => (bool) $validated['active'],
        ];

        if ($this->editingId) {
            Flow::findOrFail($this->editingId)->update($values);
        } else {
            Flow::create($values);
        }

        session()->flash('message', $this->editingId ? 'Akış güncellendi.' : 'Akış oluşturuldu.');
        $this->resetForm();
    }

    public function edit(int $flowId): void
    {
        $flow = Flow::findOrFail($flowId);
        $this->editingId = $flow->id;
        $this->form = [
            'name'                 => $flow->name,
            'event_type'           => $flow->event_type,
            'app_ids'              => array_map('strval', $flow->app_ids ?? []),
            'channels'             => $flow->channels ?? [],
            'delay_minutes'        => $flow->delay_minutes,
            'whatsapp_template_id' => $flow->whatsapp_template_id ?? '',
            'email_template_id'    => $flow->email_template_id ? (string) $flow->email_template_id : '',
            'active'               => $flow->active,
        ];
    }

    public function toggleActive(int $flowId): void
    {
        $flow = Flow::findOrFail($flowId);
        $flow->forceFill(['active' => ! $flow->active])->save();

        session()->flash('message', 'Akış durumu güncellendi.');
    }

    public function delete(int $flowId): void
    {
        Flow::findOrFail($flowId)->delete();
        session()->flash('message', 'Akış silindi.');

        if ($this->editingId === $flowId) {
            $this->resetForm();
        }
    }

    public function render(): View
    {
        $allApps = App::query()->orderBy('name')->get(['id', 'name', 'handle']);
        $allowedAppIds = $this->appIdsForEventType((string) ($this->form['event_type'] ?? 'installed'));
        $apps = $allowedAppIds === null
            ? $allApps
            : $allApps->whereIn('id', $allowedAppIds)->values();
        $wpTemplates = WpContent::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['name', 'brevo_template_id']);
        $emailTemplates = EmailContent::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'brevo_template_id']);
        $wpTemplateNamesById = WpContent::query()
            ->pluck('name', 'brevo_template_id')
            ->all();
        $emailTemplateNamesById = EmailContent::query()
            ->pluck('name', 'id')
            ->all();

        return view('livewire.shopify.shopify-flows-table', [
            'flows'                  => Flow::query()->latest()->get(),
            'apps'                   => $apps,
            'appNamesById'           => $allApps->pluck('name', 'id')->all(),
            'wpTemplates'            => $wpTemplates,
            'emailTemplates'         => $emailTemplates,
            'wpTemplateNamesById'    => $wpTemplateNamesById,
            'emailTemplateNamesById' => $emailTemplateNamesById,
            'eventTypes'             => $this->availableEventTypes(),
        ]);
    }

    /** @return array<string, string> */
    private function availableEventTypes(): array
    {
        return [
            'installed' => 'Kuruldu',
            'uninstalled' => 'Kaldırıldı',
            ...EventGenerator::query()->active()->orderBy('name')->pluck('name', 'handle')->all(),
        ];
    }

    /** @return array<int, int>|null Null means every application is allowed. */
    private function appIdsForEventType(string $eventType): ?array
    {
        if (in_array($eventType, ['installed', 'uninstalled'], true)) {
            return null;
        }

        $generator = EventGenerator::query()
            ->active()
            ->where('handle', $eventType)
            ->first(['app_ids']);

        return array_values(array_map('intval', $generator?->app_ids ?? []));
    }
}
