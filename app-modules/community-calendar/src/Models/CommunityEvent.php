<?php

namespace CorvMC\CommunityCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityEvent extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_id',
        'description',
        'start_date',
        'end_date',
        'location_name',
        'location_address',
        'latitude',
        'longitude',
        'url',
        'image_url',
        'status',
        'is_online',
        'event_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_online' => 'boolean',
    ];

    /**
     * Get the user that created the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Upload a poster image to Cloudflare R2 and update the model.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The URL of the uploaded image
     */
    public function uploadPoster(UploadedFile $file): string
    {
        //** @var \Illuminate\Support\Facades\Storage $r2 */
        $r2 = Storage::disk('r2');

        // Generate a unique filename
        $filename = 'events/' . $this->id . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Upload to Cloudflare R2
        $path = $r2->putFileAs('posters', $file, $filename);

        // Get and store the URL
        $url = $r2->url($path);
        $this->update(['image_url' => $url]);

        return $url;
    }

    /**
     * Delete the poster image from Cloudflare R2.
     *
     * @return bool
     */
    public function deletePoster(): bool
    {
        // If there's no image URL, nothing to delete
        if (!$this->image_url) {
            return true;
        }

        // Extract the path from the full URL
        $path = parse_url($this->image_url, PHP_URL_PATH);
        $path = ltrim($path, '/');

        // If path extraction failed, try to get bucket prefix from env
        if (!$path && env('CLOUDFLARE_R2_BUCKET')) {
            $bucketPrefix = env('CLOUDFLARE_R2_BUCKET') . '/';
            $path = str_replace(env('CLOUDFLARE_R2_URL') . '/' . $bucketPrefix, '', $this->image_url);
        }

        // Delete the file from R2
        $deleted = Storage::disk('r2')->delete($path);

        // Update the model if the file was deleted or doesn't exist
        if ($deleted || !Storage::disk('r2')->exists($path)) {
            $this->update(['image_url' => null]);
            return true;
        }

        return false;
    }

    /**
     * Get the full URL for the poster image.
     *
     * @return string|null
     */
    public function getPosterUrl(): ?string
    {
        return $this->image_url;
    }

    /**
     * Update the poster for this event.
     * This will delete the existing poster if there is one.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The URL of the uploaded image
     */
    public function updatePoster(UploadedFile $file): string
    {
        // Delete existing poster if there is one
        $this->deletePoster();

        // Upload new poster
        return $this->uploadPoster($file);
    }

    /**
     * Upload a poster image to Cloudflare R2 with resizing and update the model.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int|null $width Maximum width for the image
     * @param int|null $height Maximum height for the image
     * @param bool $maintainAspectRatio Whether to maintain the aspect ratio
     * @return string The URL of the uploaded image
     */
    public function uploadResizedPoster(UploadedFile $file, ?int $width = 1200, ?int $height = null, bool $maintainAspectRatio = true): string
    {
        // Create a temp file to store the resized image
        $tempPath = sys_get_temp_dir() . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Process and resize the image
        $image = \imagecreatefromstring(file_get_contents($file->getRealPath()));

        // Get original dimensions
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // Calculate new dimensions
        if ($maintainAspectRatio) {
            if ($width && !$height) {
                $ratio = $width / $origWidth;
                $height = intval($origHeight * $ratio);
            } elseif ($height && !$width) {
                $ratio = $height / $origHeight;
                $width = intval($origWidth * $ratio);
            } elseif ($width && $height) {
                $ratioWidth = $width / $origWidth;
                $ratioHeight = $height / $origHeight;
                $ratio = min($ratioWidth, $ratioHeight);

                $width = intval($origWidth * $ratio);
                $height = intval($origHeight * $ratio);
            }
        }

        // If dimensions didn't change, use the original file
        if ((!$width || $width >= $origWidth) && (!$height || $height >= $origHeight)) {
            return $this->uploadPoster($file);
        }

        // Create a new image with the calculated dimensions
        $newImage = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG files
        if ($file->getClientOriginalExtension() === 'png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
        }

        // Resample the image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        // Save the image to the temp file
        if ($file->getClientOriginalExtension() === 'jpg' || $file->getClientOriginalExtension() === 'jpeg') {
            imagejpeg($newImage, $tempPath, 90); // 90% quality
        } elseif ($file->getClientOriginalExtension() === 'png') {
            imagepng($newImage, $tempPath, 9); // Maximum compression
        } elseif ($file->getClientOriginalExtension() === 'gif') {
            imagegif($newImage, $tempPath);
        }

        // Free up memory
        imagedestroy($image);
        imagedestroy($newImage);

        // Create a new UploadedFile instance from the temp file
        $resizedFile = new UploadedFile(
            $tempPath,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            null,
            true
        );

        // Upload the resized image using the existing method
        $url = $this->uploadPoster($resizedFile);

        // Remove the temp file
        @unlink($tempPath);

        return $url;
    }

    /**
     * Handle model events to clean up associated files when deleting.
     */
    protected static function boot()
    {
        parent::boot();

        // Before deleting the model, delete the associated poster
        static::deleting(function ($event) {
            if (!$event->isForceDeleting()) {
                return;
            }

            $event->deletePoster();
        });
    }
}
