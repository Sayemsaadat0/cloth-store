<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * Category status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Check if category is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if category is inactive
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }
}

