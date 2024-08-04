<?php

namespace App\Http\Resources;

use App\Models\CartTopping;
use App\Models\Menu;
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
            'notes' => $this->notes,
            'menu_id' => $this->menu_id,
            'menu' => [
                'id' => $this->menu->id,
                'name' => $this->menu->name_menu,
                'description' => $this->menu->description,
                'price' => $this->menu->price,
                'stock' => $this->menu->stock,
            ],
            'toppings' => ToppingResource::collection($this->cartToppings),
        ];
    }
}