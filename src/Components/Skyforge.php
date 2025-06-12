<?php

namespace UntitledDevelopers\KockatoosAdminCore\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Skyforge extends Component
{
    /**
     * The database tables.
     *
     * @var array
     */
    public $tables;

    /**
     * Create a new component instance.
     */
    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('kockatoos-admin-core::components.skyforge.skyforge');
    }
}
