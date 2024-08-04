<?php

namespace App\Http\Resources;

use App\Models\CartTopping;
use App\Models\Menu;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'menu_id' => $this->menu_id,
            'menu' => Menu::find($this->menu_id),
            'variant_id' => $this->variant_id,
            'variant' =>Variant::find($this->variant_id),
            'toppings' => ToppingResource::collection($this->cartToppings),
        ];
    }
}