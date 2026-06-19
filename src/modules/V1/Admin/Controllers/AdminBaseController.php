<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers;

use App\Http\Controllers\V1\Controller;

abstract class AdminBaseController extends Controller
{
    protected $admin;

    public function __construct()
    {
    }

}
