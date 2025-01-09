<?php

namespace CivilRecords\Engine;

class Validator
{
    private $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isValid()
    {
        return empty($this->errors);
    }

    public function validate(array $post, string $object) {}
}
