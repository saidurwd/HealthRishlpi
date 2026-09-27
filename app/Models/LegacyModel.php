<?php

namespace App\Models;

use App\Models\Concerns\HasAttributeLabels;
use App\Models\Concerns\TypecastsLikeYii;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for models over the Yii app's tables.
 *
 * Relations keep the Yii relation names (country0, unit0, ...) because the
 * foreign key columns already use the plain names (country, unit, ...).
 *
 * @property int $id every legacy table has an auto-increment id
 */
abstract class LegacyModel extends Model
{
    // Options of the Active/Inactive `status` enum most lookup tables have
    public const STATUSES = ['Active' => 'Active', 'Inactive' => 'Inactive'];

    use HasAttributeLabels, TypecastsLikeYii;

    /**
     * Validation rules for a create (null) or an update of $model. Every
     * attribute a form may set must appear here; fields Yii only marked
     * "safe" get ['nullable'].
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?self $model = null): array
    {
        return [];
    }
}
