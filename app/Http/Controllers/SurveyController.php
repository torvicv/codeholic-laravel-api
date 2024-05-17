<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Spatie\Sluggable\SlugOptions;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return SurveyResource::collection(Survey::where('user_id', $user->id)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSurveyRequest $request)
    {
        $validated = $request->validated();
        $image = $request->file('image');
        if ($image) {
            $slug = str_replace(' ', '-', $validated['title']);
            $imageName = $slug.'.'.$image->extension();
            $image->storeAs('public/images', $imageName);
            $validated['image'] = 'images/'.$imageName;
        }
        $result = Survey::create($validated);
        return new SurveyResource($result);
    }

    /**
     * Display the specified resource.
     */
    public function edit(Survey $survey, Request $request)
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action.');
        }
        return new SurveyResource($survey);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSurveyRequest $request, Survey $survey)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] === 'false' ? false : true;
        $image = $request->file('image');
        if ($image) {
            $slug = str_replace(' ', '-', $validated['title']);
            $imageName = $slug.'.'.$image->extension();
            $image->storeAs('public/images', $imageName);
            $validated['image'] = 'images/'.$imageName;
        }
        $survey->update($validated);
        return new SurveyResource($survey);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Survey $survey, Request $request)
    {
        $user = $request->user();
        if ($survey->user_id != $user->id) {
            return abort(403, 'Unauthorized action.');
        }
        $survey->delete();
        return response('', 204);
    }
}
