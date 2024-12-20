<?php


class EnvironmentFileParser
{
    private array $variables = [
        
    ];

    public function __construct()
    {
        if (file_exists(__DIR__ . '/../../.env.local.php')) {
            $this->variables = require __DIR__ . '/../../.env.local.php';
        }
    }

    public function set(string $key, string $value)
    {
        $this->variables[strtoupper($key)] = $value;
        $this->dump();
    }

    public function dump()
    {
        $env_file = __DIR__ . '/../../.env.local.php';
        $content = fopen($env_file, "wb");
        if (!$content) die("impossible de créer $env_file.");
        else {
            //ecriture des paramêtres saisis
            fputs($content, "<?php \n\n");
            fputs($content, "return [\n");
            foreach ($this->variables as $key => $value) {
                fputs($content, "    '$key' => '$value',\n");
            }
            fputs($content, "];");
            fclose($content);
        }
    }
}
