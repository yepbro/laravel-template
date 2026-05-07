<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('spa', [
            'title' => 'Laravel Frontend Playground',
            'vite' => [
                'resources/css/app.css',
                'resources/js/app.ts',
            ],
        ]);
    }
}
