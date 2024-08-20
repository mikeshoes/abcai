<?php

namespace cores\driver;

interface LogDatabaseSave
{

    public function saveLog(array $message);
}