<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File attached to a goods receive line (`os_purchase_receive_document`);
 * `receive_number` is the line id. Files live in public/uploads/store.
 *
 * @property int $id
 * @property int $receive_number
 * @property string|null $doc_title
 * @property string|null $doc_file
 * @property int|null $created_by
 * @property string|null $created_on
 */
#[Table('purchase_receive_document', timestamps: false)]
#[Fillable(['receive_number', 'doc_title'])]
class PurchaseReceiveDocument extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'receive_number' => 'Receive Number', 'doc_title' => 'Title', 'doc_file' => 'Document', 'created_by' => 'Created By', 'created_on' => 'Created On'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'receive_number' => ['required', 'integer'],
            'doc_title' => ['max:255'],
            'doc_file' => ['nullable', 'file'],
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
