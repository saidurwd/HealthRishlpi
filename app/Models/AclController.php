<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * A controller listed in the access matrix (`os_acl_controller`); its
 * actions are `os_acl_action` rows.
 *
 * @property int $id
 * @property string $controller Yii controller id ("userGroup")
 * @property string $title
 * @property int|null $status
 */
#[Table('acl_controller', timestamps: false)]
#[Fillable(['controller', 'title', 'status'])]
class AclController extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'controller' => 'Controller', 'title' => 'Title', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'controller' => ['required', 'max:150', Rule::unique(self::class)->ignore($model?->getKey())],
            'title' => ['required'],
            'status' => ['nullable'],
        ];
    }

    /** @return HasMany<AclAction, $this> */
    public function aclActions(): HasMany
    {
        return $this->hasMany(AclAction::class, 'controller_id');
    }
}
