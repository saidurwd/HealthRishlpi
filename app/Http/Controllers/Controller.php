<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Response;

abstract class Controller
{
    /**
     * Response for a grid delete that hit a foreign key. The grid script
     * shows the body in an alert. (The Yii app surfaced the raw SQL error.)
     */
    protected function deleteFailed(QueryException $e): Response
    {
        if (($e->errorInfo[0] ?? null) !== '23000') {
            throw $e;
        }

        return response('This record cannot be deleted because other records refer to it.', 409);
    }
}
