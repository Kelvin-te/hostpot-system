<?php

namespace App\Http\Controllers\Api\V1;

class HealthController extends Controller
{
    public function index()
    {
        return $this->success(['status' => 'ok']);
    }
}
