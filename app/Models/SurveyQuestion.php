<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $table = 'survey__questions';

    protected $fillable = [
        'type',
        'question',
        'description',
        'data',
        'survey_id'
    ];
}
