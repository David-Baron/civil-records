<?php

namespace CivilRecords\Engine;

class MailerTemplate
{
    static public function format(string $template, array $data = [])
    {
        if (file_exists(__DIR__ . "/../../templates/email/$template")) {
            extract($data);
            ob_start();
            require __DIR__ . "/../../templates/email/$template";
            return ob_get_clean();
        }

        throw new \Exception("Template $template not found.", 1);
    }
}
