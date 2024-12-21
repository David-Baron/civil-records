<?php 

require(__DIR__ . '/../Model/ParameterDTO.php');

class AppConfiguration
{
    private array $parameters = [];

    public function __construct()
    {
        $parameterDTO = new ParameterDTO();
        $this->parameters = $parameterDTO->dataToArray();
    }

    public function get(string $key, $default = null)
    {
        if (array_key_exists($key, $this->parameters)) {
            return $this->parameters[$key];
        }

        return $default;
    }
}