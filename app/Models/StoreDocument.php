<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Http\UploadedFile;

/**
 * File attached to a store transaction (`os_store_document`). Files live
 * in public/uploads/store.
 *
 * @property int $transection_type 2 = purchase receive
 * @property int $transection_id
 * @property string|null $doc_title
 * @property string|null $doc_file
 */
#[Table('store_document', timestamps: false)]
class StoreDocument extends LegacyModel
{
    public const PURCHASE_RECEIVE = 2;

    /**
     * Save uploads for a transaction as uploads/store/<time>1_<name>, as
     * PurchaseReceiveController did.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public static function storeUploads(int $type, int $transactionId, array $files, int $userId): void
    {
        foreach ($files as $file) {
            $name = time().'1_'.str_replace(' ', '_', strtolower($file->getClientOriginalName()));
            $file->move(public_path('uploads/store'), $name);

            $document = new static;
            $document->forceFill([
                'transection_type' => $type,
                'transection_id' => $transactionId,
                'doc_title' => $file->getClientOriginalName(),
                'doc_file' => $name,
                'created_by' => $userId,
                'created_on' => now()->format('Y-m-d G:i:s'),
            ])->save();
        }
    }
}
