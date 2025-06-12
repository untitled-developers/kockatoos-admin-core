<?php

namespace UntitledDevelopers\KockatoosAdminCore\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TableDetailsLayout extends Component
{
    /**
     * The table name.
     *
     * @var string
     */
    public $table;

    /**
     * Create a new component instance.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('kockatoos-admin-core::components.skyforge.table-details-layout');
    }
}
