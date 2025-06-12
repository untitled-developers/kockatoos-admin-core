<?php

namespace UntitledDevelopers\KockatoosAdminCore\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TableDetails extends Component
{
    /**
     * The table name.
     *
     * @var string
     */
    public $table;

    /**
     * The model code.
     *
     * @var string
     */
    public $model;

    /**
     * The CRUD controller code.
     *
     * @var string
     */
    public $crudController;

    /**
     * Create a new component instance.
     */
    public function __construct(string $table, string $model, string $crudController)
    {
        $this->table = $table;
        $this->model = $model;
        $this->crudController = $crudController;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('kockatoos-admin-core::components.skyforge.table-details');
    }
}
