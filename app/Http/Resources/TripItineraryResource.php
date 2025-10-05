<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripItineraryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'day_number' => $this->day_number,
            'title' => $this->title,
            'short_title' => $this->short_title,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'distance' => $this->distance,
            'notes' => $this->notes,
            // 'place' => new PlaceResource($this->whenLoaded('place')),
            'place' => $this->place ? [
                'id' => $this->place->id,
                'name' => $this->place->name,
                'description' => $this->place->description,
                'latitude' => $this->place->latitude,
                'longitude' => $this->place->longitude,
                'media' => $this->place->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'file_path' => $media->file_path, // أو public_url لو عم تستخدم Cloudinary
                    ];
                }),
            ] : null,
            'map_location' => [
                'latitude' => optional($this->place)->latitude,
                'longitude' => optional($this->place)->longitude,
            ],
            'hotel_id' => $this->hotel_id,
            'restaurant_id' => $this->restaurant_id,
        ];
    }
}
