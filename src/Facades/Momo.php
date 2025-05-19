<?php

namespace Rais\MomoSuite\Facades;

use Illuminate\Support\Facades\Facade;

class Momo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'momo-suite';
    }
}
