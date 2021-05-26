<?php

namespace Gimmo\Interkassa\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

class Interkassa extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'interkassa';
    }
}