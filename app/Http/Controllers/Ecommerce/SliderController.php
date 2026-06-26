<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Http\Requests\SliderRequest;
use App\Models\Slider;

class SliderController extends Controller
{
    public function index()
    {
        //
    }

    public function store(SliderRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($request, $data) {

            if ($request->hasFile('image')) {
                $data['image'] = $this->storeSliderImage($request->file('image'));
            }

            $slider = Slider::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Slider created successfully.',
                'data'    => $slider,
            ], 201);

        });
    }

    private function storeSliderImage(UploadedFile $image): string
    {
        $filename = 'slider_' . now()->format('YmdHis') . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

        return $image->storeAs('sliders', $filename, 'public');
    }
}
