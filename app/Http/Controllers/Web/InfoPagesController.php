<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class InfoPagesController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('info/terms');
    }

    public function privacy(): Response
    {
        return Inertia::render('info/privacy');
    }

    public function faq(): Response
    {
        return Inertia::render('info/faq');
    }
}
