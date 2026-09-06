<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;

class PersonPhotoController extends Controller
{
    private const DISK = 'private_photos';

    public function __invoke(string $token)
    {
        $person = Student::where('photo_access_token', $token)->first()
            ?? Teacher::where('photo_access_token', $token)->first()
            ?? Staff::where('photo_access_token', $token)->first();

        abort_unless(
            $person?->photo && Storage::disk(self::DISK)->exists($person->photo),
            404
        );

        return Storage::disk(self::DISK)->response($person->photo);
    }
}