<?php

namespace CivilRecords\Engine;

use CivilRecords\Engine\PhpRenderer;

abstract class AbstractController
{
    protected PhpRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new PhpRenderer();
    }

    function render(string $template, array $data = [])
    {
        return $this->renderer->render($template, $data);
    }
}
