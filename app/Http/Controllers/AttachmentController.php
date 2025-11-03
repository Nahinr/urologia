<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function inline(Request $request, Attachment $attachment)
    {
        // Firma y permisos
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signed URL.');
        }
        $this->authorize('view', $attachment);

        $disk = $attachment->storageDisk();
        $path = $attachment->path;
        $mime = $attachment->mime ?: 'application/octet-stream';
        $filename = $attachment->original_name ?: basename($path);
        $disposition = 'inline; filename="' . addslashes($filename) . '"';

        // S3: redirige a URL temporal con metadatos de respuesta correctos
        if (in_array(config("filesystems.disks.$disk.driver"), ['s3'])) {
            $minutes = (int) config('uploads.temporary_url_minutes', 5);
            $url = Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes($minutes),
                [
                    'ResponseContentType' => $mime,
                    'ResponseContentDisposition' => $disposition,
                    // Opcional: cache control
                    'ResponseCacheControl' => 'private, max-age=0, no-store',
                ]
            );
            return redirect()->away($url);
        }

        // Local: stream con headers "inline"
        if (Storage::disk($disk)->missing($path)) {
            abort(404);
        }

        $size = Storage::disk($disk)->size($path);
        $stream = Storage::disk($disk)->readStream($path);

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    public function download(Request $request, Attachment $attachment)
    {
        // Firma y permisos
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signed URL.');
        }
        $this->authorize('download', $attachment);

        $disk = $attachment->storageDisk();
        $path = $attachment->path;
        $mime = $attachment->mime ?: 'application/octet-stream';
        $filename = $attachment->original_name ?: basename($path);
        $disposition = 'attachment; filename="' . addslashes($filename) . '"';

        // S3: redirige a URL temporal forzando descarga
        if (in_array(config("filesystems.disks.$disk.driver"), ['s3'])) {
            $minutes = (int) config('uploads.temporary_url_minutes', 5);
            $url = Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes($minutes),
                [
                    'ResponseContentType' => $mime,
                    'ResponseContentDisposition' => $disposition,
                    'ResponseCacheControl' => 'private, max-age=0, no-store',
                ]
            );
            return redirect()->away($url);
        }

        // Local: descarga
        if (Storage::disk($disk)->missing($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
