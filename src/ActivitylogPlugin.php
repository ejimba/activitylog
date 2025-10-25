<?php

namespace Rmsramos\Activitylog;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Carbon;
use Rmsramos\Activitylog\Resources\Activitylogs\ActivitylogResource;

class ActivitylogPlugin implements Plugin
{
    use EvaluatesClosures;

    protected ?string $resource = null;

    protected string|Closure|null $label = null;

    protected string|Closure|null $resourceActionLabel = null;

    protected bool|Closure $isResourceActionHidden = false;

    protected bool|Closure|null $isRestoreActionHidden = null;

    protected bool|Closure|null $isRestoreModelActionHidden = null;

    protected Closure|bool $navigationItem = true;

    protected Closure|bool $includeResource = true;

    protected string|Closure|null $navigationGroup = null;

    protected string|Closure|null $dateParser = null;

    protected string|Closure|null $dateFormat = null;

    protected string|Closure|null $datetimeFormat = null;

    protected ?Closure $datetimeColumnCallback = null;

    protected ?Closure $datePickerCallback = null;

    protected string|Closure|null $translateSubject = null;

    protected string|Closure|null $translateLogKey = null;

    protected ?string $navigationIcon = null;

    protected ?int $navigationSort = null;

    protected ?bool $navigationCountBadge = null;

    protected string|Closure|null $pluralLabel = null;

    protected bool|Closure $authorizeUsing = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'rmsramos/activitylog';
    }

    public function register(Panel $panel): void
    {
        if ($this->shouldIncludeResource()) {
            $panel->resources([
                $this->getResource(),
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getResource(): string
    {
        return $this->resource ?? $this->config('resources.class', ActivitylogResource::class);
    }

    public function getLabel(): string
    {
        return $this->evaluate($this->label)
            ?? $this->config('resources.label')
            ?? __('filament-activitylog::resource.label');
    }

    public function getResourceActionLabel(): string
    {
        return $this->evaluate($this->resourceActionLabel)
            ?? $this->config('resources.resource_action_label')
            ?? 'View Activity Log';
    }

    public function getIsResourceActionHidden(): bool
    {
        return $this->evaluate($this->isResourceActionHidden)
            ?? (bool) $this->config('resources.hide_resource_action', false);
    }

    public function getIsRestoreActionHidden(): bool
    {
        return $this->evaluate($this->isRestoreActionHidden)
            ?? (bool) $this->config('resources.hide_restore_action', false);
    }

    public function getIsRestoreModelActionHidden(): bool
    {
        return $this->evaluate($this->isRestoreModelActionHidden)
            ?? (bool) $this->config('resources.hide_restore_model_action', true);
    }

    public function getPluralLabel(): string
    {
        return $this->evaluate($this->pluralLabel)
            ?? $this->config('resources.plural_label')
            ?? __('filament-activitylog::resource.plural_label');
    }

    public function getNavigationItem(): bool
    {
        return (bool) ($this->evaluate($this->navigationItem)
            ?? $this->config('resources.navigation_item', true));
    }

    public function getNavigationGroup(): ?string
    {
        return $this->evaluate($this->navigationGroup)
            ?? $this->config('resources.navigation_group');
    }

    public function getDateFormat(): ?string
    {
        return $this->evaluate($this->dateFormat)
            ?? $this->config('date_format');
    }

    public function getDatetimeFormat(): ?string
    {
        return $this->evaluate($this->datetimeFormat)
            ?? $this->config('datetime_format');
    }

    public function getDatetimeColumnCallback(): ?Closure
    {
        return $this->datetimeColumnCallback;
    }

    public function getDatePickerCallback(): ?Closure
    {
        return $this->datePickerCallback;
    }

    public function getTranslateSubject($label): ?string
    {
        if (is_null($this->translateSubject)) {
            return $label;
        }

        return value($this->translateSubject, $label);
    }

    public function getTranslateLogKey($label): ?string
    {
        if (is_null($this->translateLogKey)) {
            return $label;
        }

        return value($this->translateLogKey, $label);
    }

    public function getDateParser(): ?Closure
    {
        return $this->dateParser ?? fn ($date) => Carbon::parse($date);
    }

    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon
            ?? $this->config('resources.navigation_icon');
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort
            ?? $this->config('resources.navigation_sort');
    }

    public function getNavigationCountBadge(): ?bool
    {
        return $this->navigationCountBadge
            ?? $this->config('resources.navigation_count_badge', false);
    }

    public function includeResource(bool|Closure $include = true): static
    {
        $this->includeResource = $include;

        return $this;
    }

    public function resource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function resourceActionLabel(string|Closure $label): static
    {
        $this->resourceActionLabel = $label;

        return $this;
    }

    public function isResourceActionHidden(bool|Closure $isHidden): static
    {
        $this->isResourceActionHidden = $isHidden;

        return $this;
    }

    public function isRestoreActionHidden(bool|Closure $isHidden): static
    {
        $this->isRestoreActionHidden = $isHidden;

        return $this;
    }

    public function isRestoreModelActionHidden(bool|Closure $isHidden): static
    {
        $this->isRestoreModelActionHidden = $isHidden;

        return $this;
    }

    public function pluralLabel(string|Closure $label): static
    {
        $this->pluralLabel = $label;

        return $this;
    }

    public function navigationItem(Closure|bool $value = true): static
    {
        $this->navigationItem = $value;

        return $this;
    }

    public function navigationGroup(string|Closure|null $group = null): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function dateParser(?Closure $parser = null): static
    {
        $this->dateParser = $parser;

        return $this;
    }

    public function dateFormat(string|Closure|null $format = null): static
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function datetimeFormat(string|Closure|null $format = null): static
    {
        $this->datetimeFormat = $format;

        return $this;
    }

    public function customizeDatetimeColumn(Closure $callable): self
    {
        $this->datetimeColumnCallback = $callable;

        return $this;
    }

    public function customizeDatePicker(Closure $callable): self
    {
        $this->datePickerCallback = $callable;

        return $this;
    }

    public function translateSubject(string|Closure|null $callable = null): static
    {
        $this->translateSubject = $callable;

        return $this;
    }

    public function translateLogKey(string|Closure|null $callable = null): static
    {
        $this->translateLogKey = $callable;

        return $this;
    }

    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationSort(int $order): static
    {
        $this->navigationSort = $order;

        return $this;
    }

    public function navigationCountBadge(bool $show = true): static
    {
        $this->navigationCountBadge = $show;

        return $this;
    }

    public function authorize(bool|Closure $callback = true): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        return $this->evaluate($this->authorizeUsing) === true;
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return config("filament-activitylog.$key", $default);
    }

    protected function shouldIncludeResource(): bool
    {
        $include = $this->evaluate($this->includeResource);

        return ($include ?? true) && (bool) $this->config('resources.enabled', true);
    }
}
