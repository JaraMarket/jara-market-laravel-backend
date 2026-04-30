<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            // 'product'    => $this->product ? new ProductResource($this->product) : null,
            'ingredient' => $this->ingredient ? new IngredientResource($this->ingredient) : null,
            'quantity' => $this->quantity,
            'price' => number_format($this->price, 2),
            'unit' => $this->unit,
        ];
    }
}
