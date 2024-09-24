<?php


namespace cores\library\Excel;

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
     * @return array
     */
    public function getErrors(): array
    {
        $data = [];
        foreach ($this->failureRows as $line => $failureRow) {
            $error = implode('\\r\\n', $failureRow->errors()) ?? '未知错误';
            $data[] = vsprintf("第【%d】行【%s】", [$line, $error]);
        }
        return $data;
    }

    public function getOriginErrors(): array
    {
        return $this->failureRows;
    }

    public function hasErrors(): bool
    {
        return count($this->getErrors()) > 0;
    }
}