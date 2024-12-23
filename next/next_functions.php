<?php 



function render(string $template, array $data = [])
{
    $ui = 'front';
    if (str_starts_with($template, 'admin')) {
        $ui = 'admin';
    }

    extract($data);

    ob_start();
    require(__DIR__ . "/../templates/$ui/$template");
    $content = ob_get_clean();
    ob_start();
    require(__DIR__ . "/../templates/$ui/_layout.php");
    return ob_get_clean();
}