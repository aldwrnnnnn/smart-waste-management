<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'photo_path',
    'waste_type',
    'confidence_score',
    'latitude',
    'longitude',
    'address',
    'description',
    'status',
    'admin_notes',
])]
class WasteReport extends Model
{
    use HasFactory;

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return url('storage/' . ltrim($this->photo_path, '/'));
    }

    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->isPending() && (int) $this->user_id === (int) $user->id;
    }

    public function canBeDeletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->isPending() && (int) $this->user_id === (int) $user->id;
    }

    public function canBeDeleted(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        return $this->canBeDeletedBy($user);
    }
}
