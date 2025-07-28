<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class UserNotFoundException extends Exception
{

    public function __construct()
    {
        parent::__construct('Oops something went wrong creating your recipe');
        Log::error('User Not found While Creating Recipe');
    }
}
