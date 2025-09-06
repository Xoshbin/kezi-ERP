<?php

namespace App\Http\Controllers\Docs;

use App\Services\DocumentationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;

class DocumentController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View
    {
        $docs = DocumentationService::make();
        $items = $docs->list();

        return View::make('docs.index', compact('items'));
    }

    public function show(Request $request, string $slug): \Illuminate\Http\Response|\Illuminate\Contracts\View\View
    {
        $docs = DocumentationService::make();
        $data = $docs->get($slug);

        $response = response()->view('docs.show', $data + ['slug' => $slug]);

        // Caching headers
        $lastModified = gmdate('D, d M Y H:i:s', $data['mtime']) . ' GMT';
        $etag = $data['etag'];

        $ifModifiedSince = $request->header('If-Modified-Since');
        $ifNoneMatch = $request->header('If-None-Match');

        if ($ifModifiedSince === $lastModified || $ifNoneMatch === $etag) {
            return response('', 304)
                ->header('Last-Modified', $lastModified)
                ->header('ETag', $etag);
        }

        return $response
            ->header('Last-Modified', $lastModified)
            ->header('ETag', $etag);
    }
}

