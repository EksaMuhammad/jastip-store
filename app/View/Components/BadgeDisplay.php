<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BadgeDisplay extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $level,
        public ?float $rating = null,
        public bool $compact = false
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.badge-display');
    }
}
