<?php


namespace cores\library\Excel;

use think\Collection;

trait FailureRowCollection
{
    protected array $failureRows = [];

    public function addFailure(int $line, array $originData, array $errors)
    {
        $failureRow = new FailureRow($line, $originData, $errors);
        $this->addFailureRow($failureRow);
    }

    public function addFailureRow(FailureRow $failureRow, $errors = [])
    {
        $line = $failureRow->getLine();

        if (!isset($this->failureRows[$line])) {
            $this->failureRows[$line] = $failureRow;
            return;
        }

        $errors = !empty($errors) ? $errors : $failureRow->errors();

        $this->failureRows[$line]->addErrors($errors);
    }

    /**
     * @return Collection
     */
    public function getErrors(): Collection
    {
        return Collection::make(array_values($this->failureRows));
    }

    public function hasErrors(): bool
    {
        return !$this->getErrors()->isEmpty();
    }
}