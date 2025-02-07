<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;


class DashboardController extends Controller
{
    //

    public function index(Request $request) {

        $baseUrl = env('API_NEOSIA');
        $token = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');

        $semesterResponse = Http::withToken($token)->get($baseUrl . '/admin_mkpk/semester');

        // dd($semesterResponse);
        $semesters = $semesterResponse->json()['semesters'];


        $semesterOptions = array_map(function ($semester) {
            $ta_semester = substr($semester['kode'], 2);
            return [
                'value' => $semester['id'],
                'label' => $semester['tahun_ajaran'] . ' (' . $semester['kode'] . ' - ' . strtoupper($semester['jenis']) . ')',
                'ta_semester' => 'TA' . $ta_semester,
                'mk_aktif' => $semester['tahun_ajaran'] . ' ' . ucfirst($semester['jenis']),
            ];
        }, $semesters);

        // dd($semesterOptions);
        $courses = $request->session()->get('courses', []);
        $courseDetails = $request->session()->get('courseDetails', []);
        $total_grafik = $request->session()->get('total_grafik', []);

        // dd($courseDetails);

        $total = count($courseDetails);
        $perPage = 10;
        $page= $request->query('page', 1);
        $paginatedCourses = new LengthAwarePaginator(
            array_slice($courseDetails, ($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Dashboard', [
            'semesterOptions' => $semesterOptions,
            'courseDetails' => $paginatedCourses,
            'total_grafik' => $total_grafik
        ]);



    }
}
