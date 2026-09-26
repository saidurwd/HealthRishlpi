<?php

namespace Tests\Unit;

use App\Support\Grid;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Grid::applyCompare() must build the same conditions as Yii's CDbCriteria::compare().
 */
class GridCompareTest extends TestCase
{
    /**
     * @return array<string, array{0: mixed, 1: bool, 2: string, 3: array<int, mixed>}>
     */
    public static function cases(): array
    {
        return [
            'empty is ignored' => ['', true, '', []],
            'operator only is ignored' => ['>', false, '', []],
            'exact' => ['5', false, '`c` = ?', ['5']],
            'partial' => ['kg', true, '`c` like ?', ['%kg%']],
            'partial escapes wildcards' => ['5%_', true, '`c` like ?', ['%5\%\_%']],
            'partial not like' => ['<>kg', true, '`c` not like ?', ['%kg%']],
            'partial with operator compares' => ['>=3', true, '`c` >= ?', ['3']],
            'exact with operator' => ['<10', false, '`c` < ?', ['10']],
            'array is IN' => [[1, 2], false, '`c` in (?, ?)', [1, 2]],
        ];
    }

    /**
     * @param  array<int, mixed>  $bindings
     */
    #[DataProvider('cases')]
    public function test_compare(mixed $value, bool $partial, string $where, array $bindings): void
    {
        $query = DB::query()->from('t');
        Grid::applyCompare($query, 'c', $value, $partial);

        $sql = $query->toSql();
        $this->assertSame($where === '' ? 'select * from `os_t`' : 'select * from `os_t` where '.$where, $sql);
        $this->assertSame($bindings, $query->getBindings());
    }
}
