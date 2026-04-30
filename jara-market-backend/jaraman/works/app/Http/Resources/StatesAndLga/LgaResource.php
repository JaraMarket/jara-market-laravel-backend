<?php

namespace App\Http\Resources\StatesAndLga;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LgaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if ($this->relationLoaded('state')) {
            $data['state'] = new StateResource($this->state);
        }

        return $data;
    }
}
