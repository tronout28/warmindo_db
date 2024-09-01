<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToppingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => optional($this->topping)->id,
            'name_topping' => optional($this->topping)->name_topping,
            'stock_topping' => optional($this->topping)->stock_topping,
            'price' => optional($this->topping)->price,
        ];
    }
}
