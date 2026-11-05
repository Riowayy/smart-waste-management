<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class WasteReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'ai_category' => CategoryResource::make($this->whenLoaded('aiCategory')),
            'ai_confidence' => $this->ai_confidence,
            'description' => $this->description,
            'image_url' => Storage::disk('public')->url($this->image_path),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'status_history' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories->map(fn ($history): array => [
                'old_status' => $history->old_status,
                'new_status' => $history->new_status,
                'note' => $history->note,
                'changed_by' => $history->changedBy?->name,
                'created_at' => $history->created_at?->toISOString(),
            ])->values()),
        ];
    }
}
