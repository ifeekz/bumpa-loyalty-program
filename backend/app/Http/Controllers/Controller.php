<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponseTrait;

/**
 * Base Controller
 *
 * All controllers extend this. Mixing ApiResponseTrait here means
 * every controller in the app gets $this->success(), $this->error(),
 * $this->notFound() etc. without any extra imports.
 */
abstract class Controller
{
    use ApiResponseTrait;
}
