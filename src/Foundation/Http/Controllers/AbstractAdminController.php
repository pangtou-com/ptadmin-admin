<?php

declare(strict_types=1);

namespace PTAdmin\Foundation\Http\Controllers;


abstract class AbstractAdminController
{
    protected function getIds(): array
    {
        $id = (int) request()->route('id');
        $ids = norm_ids(request()->get('ids'));
        if ($id) {
            $ids[] = $id;
        }
        
        return $ids;
    }
}
