<?php

namespace Kezi\Foundation\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Kezi\Foundation\Database\Factories\DocumentAttachmentFactory;

/**
 * @property int $id
 * @property int $company_id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int $uploaded_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model $attachable
 * @property-read Company $company
 * @property-read User $uploadedBy
 */
class DocumentAttachment extends Model
{
    /** @use HasFactory<DocumentAttachmentFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'document_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by_user_id',
        'attachments', // Virtual attribute for Filament compatibility
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns this attachment.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent attachable model (Invoice, PurchaseOrder, etc.).
     */
    /**
     * @return MorphTo<Model, static>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the attachment.
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Get a human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if the file exists in storage.
     */
    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Delete the file from storage when the model is deleted.
     */
    protected static function booted(): void
    {
        static::creating(function (DocumentAttachment $attachment) {
            if (! $attachment->uploaded_by_user_id) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user) {
                    $attachment->uploaded_by_user_id = (int) $user->id;
                }
            }

            if (! $attachment->company_id) {
                $tenant = \Filament\Facades\Filament::getTenant();
                if ($tenant instanceof Company) {
                    $attachment->company_id = $tenant->id;
                } elseif (\Illuminate\Support\Facades\Auth::check()) {
                    /** @var \App\Models\User $user */
                    $user = \Illuminate\Support\Facades\Auth::user();
                    $attachment->company_id = $user->current_company_id ?? $user->companies()->first()?->id;
                }
            }

            // Populate metadata if missing and file exists
            if ($attachment->file_path && Storage::disk('local')->exists($attachment->file_path)) {
                if (! $attachment->file_size) {
                    $attachment->file_size = Storage::disk('local')->size($attachment->file_path);
                }
                if (! $attachment->mime_type) {
                    $mimeType = Storage::disk('local')->mimeType($attachment->file_path);
                    if ($mimeType !== false) {
                        $attachment->mime_type = $mimeType;
                    }
                }
                if (! $attachment->file_name) {
                    // Fallback to basename if original name not provided
                    $attachment->file_name = basename($attachment->file_path);
                }
            }
        });

        static::deleting(function (DocumentAttachment $attachment) {
            // Delete file from storage
            if ($attachment->file_path && Storage::disk('local')->exists($attachment->file_path)) {
                Storage::disk('local')->delete($attachment->file_path);
            }
        });
    }

    /**
     * Set the file path from 'attachments' attribute (used by Filament).
     */
    public function setAttachmentsAttribute(mixed $value): void
    {
        $this->attributes['file_path'] = $value;
    }

    /**
     * Set the file path from 'attachment' attribute (singular fallback).
     */
    public function setAttachmentAttribute(mixed $value): void
    {
        $this->attributes['file_path'] = $value;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DocumentAttachmentFactory
    {
        return DocumentAttachmentFactory::new();
    }
}
