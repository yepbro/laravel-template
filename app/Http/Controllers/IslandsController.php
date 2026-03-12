<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class IslandsController extends Controller
{
    public function __invoke(): View
    {
        return view('islands', [
            'title' => 'Blade + Vue islands',
            'vite' => [
                'resources/css/app.css',
                'resources/js/islands/app.ts',
            ],
        ]);
    }
}
