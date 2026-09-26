<?php

namespace App\Http\Controllers;

use App\Models\Instruction;
use App\Support\Grid;

class InstructionController extends CrudController
{
    protected string $model = Instruction::class;

    protected string $route = 'instruction';

    protected string $plural = 'Instructions';

    protected string $singular = 'Instruction';

    protected function grid(): Grid
    {
        return Grid::for(Instruction::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('status', partial: true);
    }
}
