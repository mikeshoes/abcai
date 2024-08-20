<?php

namespace cores\traits;

use think\helper\Str;

trait AutoField
{
    protected function checkData(): void
    {
        foreach ($this->autoFillField as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
                $value = null;
            }

            $data = $this->getData();

            if (isset($data[$key])) {
                continue;
            }

            if (is_callable($value)) {
                $this->setAttr($key, $value());
                continue;
            }

            $method = "auto" . Str::studly($key) . "Attr";

            if (method_exists($this, $method)) {
                $this->setAttr($key, call_user_func([$this, $method]));
            }
        }
    }

    public function appendAutoField(array $field = [])
    {
        $this->autoFillField = array_unique($this->autoFillField + $field);
    }

}