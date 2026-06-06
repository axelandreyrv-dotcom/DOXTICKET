<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(string $attachment): Response
    {
        $attachmentModel = Attachment::query()
            ->where('uuid', $attachment)
            ->firstOrFail();

        if (! Storage::disk($attachmentModel->disk)->exists($attachmentModel->path)) {
            abort(404);
        }

        return response(Storage::disk($attachmentModel->disk)->get($attachmentModel->path), 200, [
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'attachment; filename="'.$attachmentModel->filename.'"',
            'Content-Type' => $attachmentModel->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
