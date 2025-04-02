<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function index()
    {
        $places = Place::with('category')->get()->map(function ($place) {
            return [
                'id' => $place->id,
                'name' => $place->name,
                'description' => $place->description,
                'lat' => $place->lat,
                'lng' => $place->lng,
                'category_id' => $place->category_id,
                'category_name' => $place->category->name,
            ];
        });
        return response()->json($places);
    }
} 