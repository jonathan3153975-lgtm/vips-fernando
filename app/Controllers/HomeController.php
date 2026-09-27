<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): array
    {
        return $this->view('home', [
            'title' => 'ImportControl',
        ]);
    }
}
