<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Status names per transaction type (`os_transection_status`): status_id
 * 0 pending, 1 approved/issued/received/transferred, 2 deleted.
 *
 * @property int $status_id
 * @property string $status_title
 * @property int $transection_type
 */
#[Table('transection_status', timestamps: false)]
class TransectionStatus extends LegacyModel
{
    // transection_type values
    public const PURCHASE_ORDER = 1;

    public const PURCHASE_RECEIVE = 2;

    public const STOCK_REQUISITION = 3;

    public const STOCK_ISSUE = 4;

    public const INVOICE = 5;

    public const STOCK_TRANSFER = 6;

    /**
     * status_id => title for one transaction type (all rows, or only the
     * user_view ones for grid filters).
     *
     * @return Collection<int, string>
     */
    public static function options(int $type, bool $userViewOnly = false): Collection
    {
        return static::query()
            ->where('transection_type', $type)
            ->when($userViewOnly, fn ($query) => $query->where('user_view', 1))
            ->orderBy('id')
            ->pluck('status_title', 'status_id');
    }

    /**
     * Coloured status label (TransectionStatus::getStatus()).
     */
    public static function badge(?int $statusId, int $type): ?HtmlString
    {
        $title = static::query()->where('status_id', $statusId)->where('transection_type', $type)->value('status_title');

        if ($title === null || $title === '') {
            return null;
        }

        $class = match ($statusId) {
            0 => 'text-bg-primary',
            1 => 'text-bg-success',
            default => 'text-bg-danger',
        };

        return new HtmlString('<span class="badge '.$class.'">'.e($title).'</span>');
    }
}
