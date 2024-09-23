<?php


namespace cores\library\Excel;


class FailureRow implements \JsonSerializable
{

    protected int $line;

    protected array $values = [];

    protected $errors = [];

    public function __construct($line, $values, $errors = [])
    {
        $this->line = $line;
        $this->values = $values;
        $this->errors = $errors;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function addErrors($errors = [])
    {
        array_push($this->errors, ... $errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $data = array_map(function ($item) {
            return $item ?? '';
        }, $this->values);
        return array_values(array_merge([
            'line' => $this->line,
        ], $data, ['error' => join('\\r\\n', $this->errors)]));
    }
}