<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

trait AddPipelineToModelTrait
{
    /**
     * @param  Builder  $query
     */
    public function scopeFilterWithPipeline($query, array $pipes)
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($pipes)
            ->thenReturn();
    }
}
