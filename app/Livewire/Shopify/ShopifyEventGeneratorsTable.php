<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Jobs\Shopify\GenerateShopifyEventsJob;
use App\Models\Shopify\App;
use App\Models\Shopify\EventGenerator;
use App\Services\Shopify\EventGeneratorEvaluator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shopify Event Oluşturucuları')]
class ShopifyEventGeneratorsTable extends Component
{
    public array $form = [];

    public ?int $editingId = null;

    public int $flashId = 0;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'name' => '', 'handle' => '', 'app_ids' => [], 'condition_logic' => 'all',
            'conditions' => [$this->emptyCondition()], 'cooldown_minutes' => 1440,
            'max_data_age_minutes' => null,
            'schedule' => ['days' => [], 'start_time' => '', 'end_time' => ''],
            'active' => true,
        ];
    }

    public function addCondition(): void
    {
        $this->form['conditions'][] = $this->emptyCondition();
    }

    public function removeCondition(int $index): void
    {
        if (count($this->form['conditions']) <= 1) {
            return;
        }
        unset($this->form['conditions'][$index]);
        $this->form['conditions'] = array_values($this->form['conditions']);
    }

    public function setDateValueMode(int $index, string $mode): void
    {
        if (!in_array($mode, ['fixed', 'dynamic'], true)) {
            return;
        }

        $this->form['conditions'][$index]['value_mode'] = $mode;
        $this->form['conditions'][$index]['value'] = $mode === 'dynamic' ? '0' : '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.handle' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:_[a-z0-9]+)*$/', Rule::unique('shopify_event_generators', 'handle')->ignore($this->editingId)],
            'form.app_ids' => ['required', 'array', 'min:1'],
            'form.app_ids.*' => ['integer', 'exists:shopify_apps,id'],
            'form.condition_logic' => ['required', 'in:all,any'],
            'form.conditions' => ['required', 'array', 'min:1'],
            'form.conditions.*.path' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'form.conditions.*.operator' => ['required', 'in:equals,not_equals,gt,gte,lt,lte'],
            'form.conditions.*.value_type' => ['required', 'in:number,date'],
            'form.conditions.*.value_mode' => ['required', 'in:fixed,dynamic'],
            'form.conditions.*.value' => ['required', 'string', 'max:500'],
            'form.cooldown_minutes' => ['required', 'integer', 'min:0', 'max:525600'],
            'form.max_data_age_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'form.schedule.days' => ['nullable', 'array'],
            'form.schedule.days.*' => ['integer', 'between:1,7'],
            'form.schedule.start_time' => ['nullable', 'date_format:H:i'],
            'form.schedule.end_time' => ['nullable', 'date_format:H:i'],
            'form.active' => ['boolean'],
        ])['form'];

        $start = $validated['schedule']['start_time'] ?? '';
        $end = $validated['schedule']['end_time'] ?? '';
        if (($start === '') !== ($end === '')) {
            $this->addError('form.schedule.start_time', 'Başlangıç ve bitiş saati birlikte girilmelidir.');
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $values = [
            'name' => trim($validated['name']), 'handle' => trim($validated['handle']),
            'app_ids' => array_values(array_unique(array_map('intval', $validated['app_ids']))),
            'condition_logic' => $validated['condition_logic'],
            'conditions' => array_map(fn (array $c) => [
                'path' => trim($c['path']),
                'operator' => $c['operator'],
                'value_type' => $c['value_type'],
                'value_mode' => $c['value_mode'],
                'value' => $c['value'],
            ], $validated['conditions']),
            'cooldown_minutes' => (int) $validated['cooldown_minutes'],
            'max_data_age_minutes' => $validated['max_data_age_minutes'] === '' ? null : $validated['max_data_age_minutes'],
            'schedule' => ['days' => array_values(array_map('intval', $validated['schedule']['days'] ?? [])), 'start_time' => $start ?: null, 'end_time' => $end ?: null],
            'active' => (bool) $validated['active'],
        ];
        $this->editingId ? EventGenerator::findOrFail($this->editingId)->update($values) : EventGenerator::create($values);
        $this->flashMessage($this->editingId ? 'Event oluşturucu güncellendi.' : 'Event oluşturucu oluşturuldu.');
        $this->resetForm();
    }

    public function edit(int $generatorId): void
    {
        $generator = EventGenerator::findOrFail($generatorId);
        $schedule = array_merge(['days' => [], 'start_time' => '', 'end_time' => ''], $generator->schedule ?? []);

        $this->resetValidation();
        $this->editingId = $generator->id;
        $this->form = [
            'name' => $generator->name, 'handle' => $generator->handle, 'app_ids' => array_map('strval', $generator->app_ids ?? []),
            'condition_logic' => $generator->condition_logic,
            'conditions' => array_map(fn (array $c) => array_merge($this->emptyCondition(), $c), $generator->conditions ?? []),
            'cooldown_minutes' => $generator->cooldown_minutes,
            'max_data_age_minutes' => $generator->max_data_age_minutes,
            'schedule' => $schedule,
            'active' => $generator->active,
        ];
    }

    public function toggleActive(int $generatorId): void
    {
        $g = EventGenerator::findOrFail($generatorId);
        $g->update(['active' => ! $g->active]);
    }

    public function delete(int $generatorId): void
    {
        EventGenerator::findOrFail($generatorId)->delete();
        $this->flashMessage('Event oluşturucu silindi. Eski event kayıtları korunuyor.');
        if ($this->editingId === $generatorId) {
            $this->resetForm();
        }
    }

    public function duplicate(int $generatorId): void
    {
        $source = EventGenerator::findOrFail($generatorId);

        EventGenerator::create([
            'name' => substr($source->name, 0, 248).' Kopya',
            'handle' => $this->nextDuplicateHandle($source->handle),
            'app_ids' => $source->app_ids,
            'conditions' => $source->conditions,
            'condition_logic' => $source->condition_logic,
            'cooldown_minutes' => $source->cooldown_minutes,
            'max_data_age_minutes' => $source->max_data_age_minutes,
            'schedule' => $source->schedule,
            'active' => false,
        ]);

        $this->flashMessage('Event oluşturucu kopyalandı ve pasif olarak oluşturuldu.');
    }

    public function preview(int $generatorId, EventGeneratorEvaluator $evaluator): void
    {
        $s = $evaluator->evaluate($generatorId, true);
        $this->flashMessage(
            "Önizleme: {$s['checked']} kayıt incelendi, {$s['matched']} eşleşme, {$s['cooldown_skipped']} cooldown, {$s['schedule_skipped']} çalışma zamanlaması ve {$s['stale_skipped']} eski veri nedeniyle atlandı. {$s['emitted']} event oluşturulabilir.",
        );
    }

    public function runNow(int $generatorId): void
    {
        GenerateShopifyEventsJob::dispatch($generatorId);
        $this->flashMessage('Event oluşturucu kuyruğa alındı.');
    }

    public function render(): View
    {
        $apps = App::query()->orderBy('name')->get(['id', 'name', 'handle']);

        return view('livewire.shopify.shopify-event-generators-table', [
            'generators' => EventGenerator::query()->latest()->get(), 'apps' => $apps,
            'appNamesById' => $apps->mapWithKeys(fn (App $app) => [$app->id => $app->name.' ('.$app->handle.')'])->all(),
        ]);
    }

    private function emptyCondition(): array
    {
        return ['path' => '', 'operator' => 'equals', 'value_type' => 'number', 'value_mode' => 'fixed', 'value' => ''];
    }

    /** @param array<string, mixed> $condition */
    public function formatConditionValue(array $condition): string
    {
        if (($condition['value_type'] ?? null) !== 'date' || ($condition['value_mode'] ?? 'fixed') !== 'dynamic') {
            return (string) ($condition['value'] ?? '-');
        }

        $days = (int) ($condition['value'] ?? 0);

        return match (true) {
            $days > 0 => "Şimdi + {$days} gün",
            $days < 0 => 'Şimdi - '.abs($days).' gün',
            default => 'Şimdi',
        };
    }

    private function flashMessage(string $message): void
    {
        $this->flashId++;
        session()->flash('message', $message);
    }

    private function nextDuplicateHandle(string $handle): string
    {
        $attempt = 1;

        do {
            $suffix = $attempt === 1 ? '_copy' : '_copy_'.$attempt;
            $candidate = substr($handle, 0, 100 - strlen($suffix)).$suffix;
            $attempt++;
        } while (EventGenerator::query()->where('handle', $candidate)->exists());

        return $candidate;
    }
}
