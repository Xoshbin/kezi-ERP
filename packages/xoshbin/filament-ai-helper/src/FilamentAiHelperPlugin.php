<?php

namespace Xoshbin\FilamentAiHelper;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;

class FilamentAiHelperPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $enabled = true;
    protected ?string $buttonLabel = null;
    protected ?string $buttonIcon = null;
    protected ?string $brandName = null;
    protected ?string $modalWidth = null;
    protected ?string $position = null;
    protected ?string $theme = null;
    protected ?bool $enableWelcomeMessage = null;
    protected array $contextMapping = [];

    public function getId(): string
    {
        return 'filament-ai-helper';
    }

    public function register(Panel $panel): void
    {
        // Register the chat widget at the end of the body
        $panel->renderHook(
            'panels::body.end',
            fn (): string => view('filament-ai-helper::chat-widget', [
                'enabled' => $this->enabled,
                'buttonLabel' => $this->getButtonLabel(),
                'buttonIcon' => $this->getButtonIcon(),
                'brandName' => $this->getBrandName(),
                'modalWidth' => $this->getModalWidth(),
                'position' => $this->getPosition(),
                'theme' => $this->getTheme(),
                'enableWelcomeMessage' => $this->getEnableWelcomeMessage(),
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

    public function brandName(string | \Closure | null $name): static
    {
        $this->brandName = $name;

        return $this;
    }

    public function position(string | \Closure | null $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function theme(string | \Closure | null $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function enableWelcomeMessage(bool | \Closure | null $condition): static
    {
        $this->enableWelcomeMessage = $condition;

        return $this;
    }

    public function contextMapping(array $mapping): static
    {
        $this->contextMapping = $mapping;

        return $this;
    }

    protected function getButtonLabel(): string
    {
        return $this->evaluate($this->buttonLabel) ?? config('filament-ai-helper.ui.button_label', 'AI Assistant');
    }

    protected function getButtonIcon(): string
    {
        return $this->evaluate($this->buttonIcon) ?? config('filament-ai-helper.ui.button_icon', 'heroicon-o-sparkles');
    }

    protected function getBrandName(): string
    {
        return $this->evaluate($this->brandName) ?? config('filament-ai-helper.ui.brand_name', 'AI Assistant');
    }

    protected function getModalWidth(): string
    {
        return $this->evaluate($this->modalWidth) ?? config('filament-ai-helper.ui.modal_width', 'lg');
    }

    protected function getPosition(): string
    {
        return $this->evaluate($this->position) ?? config('filament-ai-helper.ui.position', 'bottom-right');
    }

    protected function getTheme(): string
    {
        return $this->evaluate($this->theme) ?? config('filament-ai-helper.ui.theme', 'auto');
    }

    protected function getEnableWelcomeMessage(): bool
    {
        return $this->evaluate($this->enableWelcomeMessage) ?? config('filament-ai-helper.ui.enable_welcome_message', true);
    }

    public function getContextMapping(): array
    {
        return $this->contextMapping ?: config('filament-ai-helper.assistant.context_mapping', []);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
