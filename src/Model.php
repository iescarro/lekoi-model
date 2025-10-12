<?php

namespace Lekoi;

class Model
{
    protected IDatabase $db;

    function __construct()
    {
        $this->db = DB::$db;
    }
}
