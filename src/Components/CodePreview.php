<?php

namespace UntitledDevelopers\KockatoosAdminCore\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CodePreview extends Component
{
    /**
     * The language to use for syntax highlighting.
     *
     * @var string
     */
    public $language;

    /**
     * The type of code being displayed.
     *
     * @var string
     */
    public $type;

    /**
     * The title for the code section.
     *
     * @var string
     */
    public $title;

    /**
     * Create a new component instance.
     */
    public function __construct(string $language, string $type, string $title)
    {
        $this->language = $language;
        $this->type = $type;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('kockatoos-admin-core::components.skyforge.code-preview');
    }
}
