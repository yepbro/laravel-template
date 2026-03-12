<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SpaController extends Controller
{
    public function __invoke(): View
    {
        return view('spa', [
            'title' => 'Vue-only SPA',
            'vite' => [
                'resources/css/app.css',
                'resources/js/app.ts',
            ],
        ]);
    }
}
