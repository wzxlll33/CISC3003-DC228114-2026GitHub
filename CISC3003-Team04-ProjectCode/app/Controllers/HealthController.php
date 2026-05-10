<?php

namespace App\Controllers;

use App\Core\Controller;

class HealthController extends Controller
{
    public function index(): void
    {
        $this->json(['status' => 'ok']);
    }
}
