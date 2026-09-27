<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Document numbers (INV#, PRE#, PAT#, SR#, SI#, PO/, MRR/, ST/) continue from
 * the newest saved one, so two saves at the same moment (a double click is
 * enough) used to get the same number. locked() holds a database named
 * lock per sequence from choosing the number until the row is saved, so
 * requests of this app take turns. Call it outside any transaction, or
 * around the whole transaction, so the lock is held until the commit.
 */
class DocumentNumber
{
    /** Seconds to wait for another save of the same sequence */
    public static int $timeout = 10;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback  chooses the number and saves the row
     * @return T
     */
    public static function locked(string $sequence, Closure $callback): mixed
    {
        $name = self::lockName($sequence);
        $acquired = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$name, self::$timeout])->acquired;

        if ((int) $acquired !== 1) {
            throw new RuntimeException("The next $sequence number is busy; please try again.");
        }

        try {
            return $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
        }
    }

    public static function lockName(string $sequence): string
    {
        // Named locks are server-wide: include the database name (64 chars max)
        return substr(DB::getDatabaseName().'.number.'.$sequence, 0, 64);
    }
}
