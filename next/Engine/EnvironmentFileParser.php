<?php


class EnvironmentFileParser
{
    private array $env = [];

    public function __construct()
    {
        if (file_exists(__DIR__ . '/../../.env.local.php')) {
            $this->env = require __DIR__ . '/../../.env.local.php';
        }
    }

    public function set(string $key, string $value)
    {
        $this->env[strtoupper($key)] = $value;
        $this->dump();
    }

    public function dump()
    {
        $env_file = __DIR__ . '/../../.env.local.php';
        $content = fopen($env_file, "wb");
        if (!$content) {
            throw new \Exception("impossible de créer $env_file.", 1);
        } else {
            //ecriture des paramêtres saisis
            fputs($content, "<?php \n\n");
            fputs($content, "return [\n");
            foreach ($this->env as $key => $value) {
                fputs($content, "    '$key' => '$value',\n");
            }
            fputs($content, "];");
            fclose($content);
        }
    }
}
