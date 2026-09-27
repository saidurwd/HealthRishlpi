<?php

namespace App\Models;

use App\Models\Concerns\HasAttributeLabels;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * One activity log entry: who changed which record, and how.
 */
class Activity extends SpatieActivity
{
    use HasAttributeLabels;

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'created_at' => 'Time',
            'causer_id' => 'User',
            'log_name' => 'Log',
            'event' => 'Action',
            'subject_type' => 'Record',
            'subject_id' => 'Record ID',
            'description' => 'Description',
        ];
    }

    /**
     * "Invoice Parent" for App\Models\InvoiceParent.
     */
    public static function typeLabel(?string $type): string
    {
        return $type === null ? '' : Str::headline(class_basename($type));
    }

    /**
     * Changed fields as "field: old → new" lines (escaped HTML).
     */
    public function changesHtml(): string
    {
        $changes = $this->attribute_changes?->all() ?? [];
        $new = $changes['attributes'] ?? [];
        $old = $changes['old'] ?? [];
        $lines = [];

        foreach (array_keys($new + $old) as $field) {
            $before = array_key_exists($field, $old) ? self::show($old[$field]) : null;
            $after = array_key_exists($field, $new) ? self::show($new[$field]) : null;
            $lines[] = '<strong>'.e($field).'</strong>: '.match (true) {
                $before === null => e($after),
                $after === null => '<del>'.e($before).'</del>',
                default => e($before).' &rarr; '.e($after),
            };
        }

        foreach ($this->properties?->all() ?? [] as $key => $value) {
            $lines[] = '<strong>'.e($key).'</strong>: '.e(self::show($value));
        }

        return implode('<br>', $lines);
    }

    private static function show(mixed $value): string
    {
        return match (true) {
            $value === null => '(empty)',
            is_array($value) => implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value)),
            is_bool($value) => $value ? 'yes' : 'no',
            default => Str::limit((string) $value, 200),
        };
    }
}
