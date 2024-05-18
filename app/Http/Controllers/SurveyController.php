<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Sluggable\SlugOptions;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return SurveyResource::collection(Survey::where('user_id', $user->id)->orderBy('expire_date', 'ASC')->paginate());
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
        $survey = Survey::create($validated);
        foreach ($validated['questions'] as $question) {
            $question['survey_id'] = $survey->id;
            $this->createQuestion($question);
        }
        return new SurveyResource($survey);
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
        $questions = [];
        foreach ($validated['questions'] as $question) {
            $question['survey_id'] = $survey->id;
            array_push($questions, $this->createQuestion($question));
        }
        $survey->update($validated);
        $survey->survey_questions()->delete();
        $survey->survey_questions()->saveMany($questions);
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

    private function createQuestion($data) {
        $checkData = false;
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        if (isset($data['data']) && isset(json_decode($data['data'])->options) && count(json_decode($data['data'])->options) > 0) {
            $checkData = true;
        }

        if (!isset($data['data'])) {
            $data['data'] = json_encode('{}');
        }

        $values = [
            'text', 'select', 'radio', 'checkbox', 'textarea'
        ];

        $validator = Validator::make($data, [
            'type' => ['required', Rule::in($values)],
            'question' =>'required|string',
            'description' =>'nullable|string',
            'data' =>'present',
            'survey_id' => 'exists:App\Models\Survey,id'
        ]);

        //if ($checkData) {
          //  $surveyQuestion = SurveyQuestion::where('survey_id', $validator->validated()['survey_id']);
            // return $surveyQuestion->update($validator->validated());
          //  return $surveyQuestion;
        //}
        // return SurveyQuestion::create($validator->validated());
        return new SurveyQuestion($validator->validated());
    }
}
