<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvDocument;
use App\Models\CvProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InternalVPeopleCvFileController extends Controller
{
    public function document(Request $request, CvDocument $document)
    {
        $hash = $this->validatedHash($request);
        $document->loadMissing('cvProfile.user');

        abort_unless(
            $document->cvProfile
                && $document->cvProfile->user
                && hash_equals((string) $document->cvProfile->user->vpeople_nik_hash, $hash),
            404
        );

        return $this->privateFileResponse($document->file_path, $document->original_name, $document->mime_type);
    }

    public function photo(Request $request, CvProfile $profile)
    {
        $hash = $this->validatedHash($request);
        $profile->loadMissing('user');

        abort_unless(
            $profile->user && hash_equals((string) $profile->user->vpeople_nik_hash, $hash),
            404
        );

        return $this->privateFileResponse($profile->photo_path, 'foto-vitae-' . $profile->id, null);
    }

    private function validatedHash(Request $request): string
    {
        return (string) $request->validate([
            'hash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ])['hash'];
    }

    private function privateFileResponse(?string $path, string $name, ?string $mimeType)
    {
        abort_if(empty($path) || !Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $name, [
            'Content-Type' => $mimeType ?: (Storage::disk('local')->mimeType($path) ?: 'application/octet-stream'),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
