<?php

namespace AccounTech\FilamentAiHelper;

use AccounTech\FilamentAiHelper\Actions\AiHelperHeaderAction;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class FilamentAiHelperPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $enabled = true;
    protected ?string $buttonLabel = null;
    protected ?string $buttonIcon = null;
    protected ?string $modalWidth = null;

    public function getId(): string
    {
        return 'filament-ai-helper';
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            'panels::global-search.end',
            fn (): string => view('filament-ai-helper::ai-helper-action', [
                'enabled' => $this->enabled,
                'buttonLabel' => $this->getButtonLabel(),
                'buttonIcon' => $this->getButtonIcon(),
                'modalWidth' => $this->getModalWidth(),
            ])->render()
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function enabled(bool | \Closure $condition = true): static
    {
        $this->enabled = $this->evaluate($condition);

        return $this;
    }

    public function buttonLabel(string | \Closure | null $label): static
    {
        $this->buttonLabel = $label;

        return $this;
    }

    public function buttonIcon(string | \Closure | null $icon): static
    {
        $this->buttonIcon = $icon;

        return $this;
    }

    public function modalWidth(string | \Closure | null $width): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    protected function getButtonLabel(): string
    {
        return $this->evaluate($this->buttonLabel) ?? config('filament-ai-helper.ui.button_label', 'AccounTech Pro');
    }

    protected function getButtonIcon(): string
    {
        return $this->evaluate($this->buttonIcon) ?? config('filament-ai-helper.ui.button_icon', 'heroicon-o-sparkles');
    }

    protected function getModalWidth(): string
    {
        return $this->evaluate($this->modalWidth) ?? config('filament-ai-helper.ui.modal_width', 'lg');
    }
}
