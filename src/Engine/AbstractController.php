<?php

namespace CivilRecords\Engine;

use CivilRecords\Engine\TwigRenderer;

abstract class AbstractController
{
    protected TwigRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new TwigRenderer();
    }

    function render(string $template, array $data = [])
    {
        return $this->renderer->render($template, $data);
    }
}
