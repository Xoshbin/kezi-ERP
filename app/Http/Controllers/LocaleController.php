<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Set the application locale and store it in session.
     */
    public function setLocale(Request $request, string $locale): Response|JsonResponse
    {
        // Validate locale against supported locales
        $supportedLocales = ['en', 'ckb', 'ar'];

        if (!in_array($locale, $supportedLocales)) {
            return response('Invalid locale', 400);
        }

        // Store locale in session
        Session::put('locale', $locale);

        // Set locale for current request
        App::setLocale($locale);

        // If this is an AJAX request, return success with redirect URL if on docs page
        if ($request->ajax()) {
            $response = ['status' => 'success'];

            // If the referer is a docs page, provide the equivalent URL in the new locale
            $referer = $request->header('referer');
            if ($referer && str_contains($referer, '/docs/')) {
                $response['redirect_url'] = $this->getLocaleEquivalentUrl($referer, $locale);
            }

            return response()->json($response, 200);
        }

        // For regular requests, redirect back
        return response('Locale set successfully', 200);
    }

    /**
     * Get the equivalent URL for a docs page in the specified locale.
     */
    private function getLocaleEquivalentUrl(string $url, string $locale): string
    {
        // Extract the slug from the URL
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || !str_starts_with($path, '/docs/')) {
            return url('/docs');
        }

        $slug = substr($path, 6); // Remove '/docs/' prefix

        if (empty($slug)) {
            return url('/docs');
        }

        // Determine base slug (strip existing locale suffix)
        $baseSlug = $slug;
        if (str_ends_with($slug, '.ar')) {
            $baseSlug = substr($slug, 0, -3);
        } elseif (str_ends_with($slug, '.ckb')) {
            $baseSlug = substr($slug, 0, -4);
        }

        // Build the new slug for the target locale
        $newSlug = $baseSlug;
        if ($locale === 'ar') {
            $newSlug .= '.ar';
        } elseif ($locale === 'ckb') {
            $newSlug .= '.ckb';
        }

        return url('/docs/' . $newSlug);
    }
}
