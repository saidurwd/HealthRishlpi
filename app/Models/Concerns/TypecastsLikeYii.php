<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * Port of CDbColumnSchema::typecast(), which Yii applied to every value it
 * wrote. For an empty string:
 *   - nullable numeric/boolean column  => NULL
 *   - NOT NULL integer/boolean column  => 0
 *   - text, enum and date columns keep '' (MySQL non-strict mode turns ''
 *     into a zero date, as it did under Yii)
 *
 * On insert Yii also left out NULL values of NOT NULL columns
 * (CDbCommandBuilder::createInsertCommand()), so MySQL filled in the
 * column default (0 for a NOT NULL int without one). Service invoice lines
 * rely on this: their `item` is stored as 0.
 */
trait TypecastsLikeYii
{
    /** @var array<string, array<string, array{kind: string, nullable: bool}>> */
    private static array $yiiColumnTypes = [];

    public static function bootTypecastsLikeYii(): void
    {
        static::saving(function (self $model) {
            $model->typecastEmptyStrings();
        });

        static::creating(function (self $model) {
            $model->dropNullsOfNotNullColumns();
        });
    }

    protected function dropNullsOfNotNullColumns(): void
    {
        $columns = $this->yiiColumnTypes();

        foreach ($this->attributes as $attribute => $value) {
            if ($value === null && isset($columns[$attribute]) && ! $columns[$attribute]['nullable']) {
                unset($this->attributes[$attribute]);
            }
        }
    }

    protected function typecastEmptyStrings(): void
    {
        $columns = $this->yiiColumnTypes();

        foreach ($this->getDirty() as $attribute => $value) {
            if ($value !== '' || ! isset($columns[$attribute]) || $columns[$attribute]['kind'] === 'string') {
                continue;
            }

            $column = $columns[$attribute];
            $this->attributes[$attribute] = match (true) {
                $column['nullable'] => null,
                $column['kind'] === 'double' => '',
                default => 0,
            };
        }
    }

    /**
     * @return array<string, array{kind: string, nullable: bool}>
     */
    private function yiiColumnTypes(): array
    {
        $table = $this->getTable();

        return self::$yiiColumnTypes[$table] ??= collect(Schema::getColumns($table))
            ->mapWithKeys(fn (array $column) => [$column['name'] => [
                'kind' => self::yiiKind($column['type_name'], $column['type']),
                'nullable' => (bool) $column['nullable'],
            ]])
            ->all();
    }

    /**
     * Yii's CMysqlColumnSchema::extractType() buckets.
     */
    private static function yiiKind(string $typeName, string $type): string
    {
        return match (true) {
            str_contains($type, 'tinyint(1)'), str_starts_with($typeName, 'bit'), str_starts_with($typeName, 'bool') => 'boolean',
            str_contains($typeName, 'int') => 'integer',
            in_array($typeName, ['float', 'double', 'decimal', 'real', 'numeric'], true) => 'double',
            default => 'string',
        };
    }
}
