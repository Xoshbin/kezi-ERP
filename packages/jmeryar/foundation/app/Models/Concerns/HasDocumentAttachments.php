<?php

namespace Jmeryar\Foundation\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jmeryar\Foundation\Models\DocumentAttachment;

/**
 * Trait HasDocumentAttachments
 *
 * Provides document attachment functionality to models via polymorphic relationship.
 * Include this trait in models that need file attachment support (Invoice, PurchaseOrder, etc.).
 */
trait HasDocumentAttachments
{
    /**
     * Get all document attachments for this model.
     *
     * @return MorphMany<DocumentAttachment>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }

    /**
     * Get the storage directory for attachments of this model.
     * Override this method in your model to customize the storage path.
     */
    public function getAttachmentStorageDirectory(): string
    {
        // Default: document-attachments/{model-name-plural}
        // e.g., document-attachments/invoices, document-attachments/purchase-orders
        $modelName = class_basename(static::class);
        $directory = str($modelName)->plural()->kebab()->toString();

        return "document-attachments/{$directory}";
    }
}
