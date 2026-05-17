<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Inertia\Inertia;
use Inertia\Response;

class InfoPagesController extends Controller
{
    public function terms(): Response
    {
        return $this->render('info/terms', 'terms', 'Terms & Conditions');
    }

    public function privacy(): Response
    {
        return $this->render('info/privacy', 'privacy', 'Privacy Policy');
    }

    public function faq(): Response
    {
        return $this->render('info/faq', 'faq', 'Frequently Asked Questions');
    }

    protected function render(string $component, string $slug, string $defaultTitle): Response
    {
        $page = LegalPage::query()->where('slug', $slug)->first();

        return Inertia::render($component, [
            'page' => [
                'title' => $page ? $page->title : $defaultTitle,
                'body' => $page ? (string) $page->body : '',
                'last_updated_label' => $page?->last_updated_label,
            ],
        ]);
    }
}
