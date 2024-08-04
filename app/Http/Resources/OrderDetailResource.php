<?php

namespace App\Http\Resources;

use App\Models\OrderDetailTopping;
use App\Models\Menu;
use App\Models\Variant;
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
            'variant_id' => $this->variant_id,
            'variant' => Variant::find($this->variant_id),
            'toppings' => ToppingResource::collection(OrderDetailTopping::where('order_detail_id', $this->id)->get()),
        ];
    }
}
