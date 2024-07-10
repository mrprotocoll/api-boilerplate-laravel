<?php

declare(strict_types=1);

namespace Shared\Services;

use Exception;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Shared\Helpers\ResponseHelper;

final class FileService
{
    private string $name;

    private string $originalName;

    private string $mime;

    private string $path;

    private string $disk;

    private string $hash;

    private int $size;

    private ?string $collection = null;

    public function __construct(string $name, string $originalName, string $mime, string $path, string $disk, string $hash, int $size, ?string $collection = null)
    {
        $this->name = $name;
        $this->originalName = $originalName;
        $this->mime = $mime;
        $this->path = $path;
        $this->disk = $disk;
        $this->hash = $hash;
        $this->size = $size;
        $this->collection = $collection;
    }

    public function toArray(): array
    {
        return [
            'file_display_name' => $this->name,
            'file_name' => $this->originalName,
            'file_mime_type' => $this->mime,
            'file_path' => $this->path,
            'file_size' => $this->size,
            'file_disk' => $this->disk,
            'file_hash' => $this->hash,
            'file_collection' => $this->collection,
        ];
    }

    public static function formatName($filename): string
    {
        return time() . '_' . str_replace(' ', '_', $filename);
    }

    public static function filenameWithoutExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Store file in storage and return file details as an array
     *
     * @param  string  $folderName  folder name to store file. Leave empty to store in public folder
     * @return \Illuminate\Http\JsonResponse | array
     */
    public static function upload(UploadedFile $file, string $folderName = ''): false|array
    {
        try {
            $name = $file->getClientOriginalName();
            $fileName = "{$folderName}/" . self::formatName($name);
            $disk = 'public';
            // Store the file in the 'public' disk (storage/app/public)
            Storage::disk($disk)->put($fileName, file_get_contents($file));

            // Generate SHA-256 hash for the file
            $fileHash = hash('sha256', file_get_contents($file));

            return (new self(
                name: $name,
                originalName: $fileName,
                mime: $file->getClientMimeType(),
                path: Storage::disk($disk)->url($fileName),
                disk: $disk,
                hash: $fileHash,
                size: $file->getSize(),
                collection: $folderName
            ))->toArray();
        } catch (Exception $e) {
            Log::channel('upload_error')->error("upload Failed: \n" . $e->getMessage());

            return false;
        }
    }

    /**
     * Store base64-encoded file in storage and return file details as an array
     *
     * @param  string  $folderName  folder name to store file. Leave empty to store in the public folder
     */
    public static function uploadBase64(string $base64File, string $folderName = ''): \Illuminate\Http\JsonResponse|array
    {
        try {
            // Decode the base64 data
            $decodedFile = base64_decode($base64File);

            // Use finfo to get the mime type of the file
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($decodedFile);

            // Determine the extension based on the mime type
            $extension = self::getExtensionFromMimeType($mime);

            // Generate a unique name for the file
            $name = uniqid() . '_' . time() . '.' . $extension;

            // Specify the file path in the 'public' disk
            $filePath = $folderName . '/' . $name;

            // Store the file using Storage
            Storage::disk('uploads')->put($filePath, $decodedFile);

            return (new self(
                name: $name,
                originalName: $filePath,
                mime: $mime,
                path: Storage::disk('uploads')->url($filePath),
                disk: 'uploads',
                hash: hash('sha256', $decodedFile),
                size: mb_strlen($decodedFile),
                collection: $folderName
            ))->toArray();
        } catch (Exception $e) {
            return ResponseHelper::error('Could not upload file');
        }
    }

    /**
     * Get the file extension based on the mime type
     */
    private static function getExtensionFromMimeType(string $mime): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            // Add more mime type to extension mappings as needed
        ];

        return $extensions[$mime] ?? 'dat'; // Default to 'dat' if the mime type is unknown
    }
}
