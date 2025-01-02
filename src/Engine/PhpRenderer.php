<?php

namespace CivilRecords\Engine;

class PhpRenderer
{
    protected array $data = [];

    public function render(string $template, array $data = [])
    {
        // $this->data['root'] = $_ENV['APP_ROOT'];
        $this->data = array_merge($this->data, $data);

        $ui = 'front';
        if (str_starts_with($template, 'next_admin')) {
            $ui = 'next_admin';
        }

        extract($this->data);

        ob_start();
        require(__DIR__ . "/../../templates/$template");
        $content = ob_get_clean();
        ob_start();
        require(__DIR__ . "/../../templates/$ui/_layout.php");
        return ob_get_clean();
    }
}
