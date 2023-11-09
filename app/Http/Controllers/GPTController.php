<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Orhanerday\OpenAi\OpenAi;

class GPTController extends Controller
{
    public function models()
    {

        $open_ai = new OpenAi(env('OPENAI_API_KEY'));

        dd($open_ai->listModels());
    }
}
