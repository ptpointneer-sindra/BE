<?php

namespace App\Services;

use App\Models\Instansi;

class InstansiService
{
    public function getAllInstansi()
    {
        return Instansi::all()->toArray();
    }
}

