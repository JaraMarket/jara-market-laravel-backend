<?php

namespace App\Filters\CategoryFilter;

use App\Filters\BaseFilter;

class Type extends BaseFilter
{
    protected function applyFilter($builder)
    {
        return $builder->where(function ($query) {
            $search = request($this->filterName());
            $query->where($this->filterName(), $search);
        });
    }
}
