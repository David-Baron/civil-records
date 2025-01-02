<?php

namespace CivilRecords\Engine;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    protected Environment $twig;
    protected array $data = [];

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => false, //' __DIR__ . /../../var/cache',
        ]);
    }

    public function render(string $template, array $data = [])
    {
        $this->data = array_merge($this->data, $data);
        return $this->twig->render($template, $this->data);
    }
}
