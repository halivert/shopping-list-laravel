<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as IlluminateController;

abstract class Controller extends IlluminateController
{
    use AuthorizesRequests;
}
