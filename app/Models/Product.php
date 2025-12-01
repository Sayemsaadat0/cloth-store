<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'thumbnail',
        'description',
        'category_id',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the full URL for the product thumbnail.
     * Returns the value as-is if it's already a full URL (http:// or https://),
     * otherwise prepends the base URL for relative paths.
     *
     * @return string|null
     */
    public function getThumbnailAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If it's already a full URL (starts with http:// or https://), return as-is
        if (preg_match('/^https?:\/\//', $value)) {
            return $value;
        }

        // Otherwise, treat it as a relative path and prepend base URL
        $assetUrl = config('app.asset_url');
        
        if ($assetUrl) {
            // Remove trailing slash from asset_url if present
            $assetUrl = rtrim($assetUrl, '/');
            // Remove leading slash from thumbnail if present
            $thumbnail = ltrim($value, '/');
            return $assetUrl . '/' . $thumbnail;
        }

        // Fallback to APP_URL if ASSET_URL is not set
        $appUrl = rtrim(config('app.url'), '/');
        $thumbnail = ltrim($value, '/');
        return $appUrl . '/' . $thumbnail;
    }
}

