<?php

namespace App\Services;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaStorage
{
    private const STORAGE_PREFIX = 'supabase/';

    public function store(UploadedFile $file, string $directory): string
    {
        $path = self::STORAGE_PREFIX.$directory.'/'.Str::uuid().'.'.$file->guessExtension();
        $disk = Storage::disk('s3');

        if ($disk instanceof AwsS3V3Adapter) {
            // Supabase controls public downloads at the bucket level, not through object ACLs.
            $contents = $file->get();

            if ($contents === false || $contents === '') {
                throw new RuntimeException('The image could not be read.');
            }

            $disk->getClient()->putObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $path,
                'Body' => $contents,
                'ContentType' => $file->getMimeType(),
            ]);
        } else {
            $storedPath = $file->store(self::STORAGE_PREFIX.$directory, 's3');

            if ($storedPath === false) {
                throw new RuntimeException('The image could not be stored in object storage.');
            }

            $path = $storedPath;
        }

        return $path;
    }

    public function url(?string $path): string
    {
        if (blank($path)) {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        $disk = Str::startsWith($path, self::STORAGE_PREFIX) ? 's3' : 'public';

        $filesystem = Storage::disk($disk);

        if (! $filesystem instanceof FilesystemAdapter) {
            throw new RuntimeException("The {$disk} filesystem does not support URL generation.");
        }

        return $filesystem->url($path);
    }

    public function delete(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return;
        }

        $disk = Str::startsWith($path, self::STORAGE_PREFIX) ? 's3' : 'public';

        Storage::disk($disk)->delete($path);
    }
}
