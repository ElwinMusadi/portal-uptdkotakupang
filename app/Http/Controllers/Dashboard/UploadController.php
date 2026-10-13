<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\StoreUpload;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreUploadRequest;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Files uploaded from the dashboard (D-028), in the shape of the
 * dashboard's Berkas: name, size, type and the address to read it from.
 */
class UploadController extends Controller
{
    /**
     * Store a file for its target. It stays unused, and is removed after a
     * day, until content that uses it is saved.
     */
    public function store(StoreUploadRequest $request, StoreUpload $storeUpload): JsonResponse
    {
        /** @var UploadTarget $target */
        $target = $request->target();
        $upload = $storeUpload->handle($request->user(), $target, $request->file('berkas'));

        return response()->json([
            'nama' => $upload->nama,
            'ukuran' => $upload->ukuran,
            'tipe' => $upload->tipe,
            'url' => $upload->url(),
        ], 201);
    }

    /**
     * Stream a private file to an employee allowed to read it. Public files
     * are read from the public bucket instead.
     */
    public function show(Upload $upload): StreamedResponse
    {
        abort_if($upload->publik || ! $upload->disk()->exists($upload->path), 404);

        return $upload->disk()->response($upload->path, $upload->nama, [
            'Content-Type' => $upload->tipe,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
