<?php

namespace App\Http\Resources;

use App\Models\carttopping;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
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
            'menu' => Menu::find($this->menu_id),
            'toppings' => ToppingResource::collection(carttopping::where('cart_id', $this->id)->get()),
        ];
    }
}