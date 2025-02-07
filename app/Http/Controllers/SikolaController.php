<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SikolaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function byCourseId(Request $request)
    {

        $baseUrl = env('URL_API_SIKOLA');
        $wstoken = env('TOKEN_SIKOLA');
        $field = request('field');
        $value = request('value');
        $response = Http::get($baseUrl, [
            'wstoken' => $wstoken,
            'moodlewsrestformat' => 'json',
            'wsfunction' => 'core_course_get_courses_by_field',
            'field' => $field,
            'value' => $value,
        ]);

        return response()->json($response->json());
    }






    /**
     * Store a newly created resource in storage.
     */
    public function redirect(Request $request)
    {
        $url = request('url');
        return Inertia::location($url);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
