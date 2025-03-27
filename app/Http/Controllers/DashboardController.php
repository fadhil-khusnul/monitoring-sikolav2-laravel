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

    public function getSemesters(){

        $baseUrl = env('API_NEOSIA');
        $token = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');

        $semesterResponse = Http::withToken($token)->get($baseUrl . '/admin_mkpk/semester');

        // dd($semesterResponse);
        $semesters = $semesterResponse->json()['semesters'] ?? [];

        $semesters = array_slice($semesters, 0, 3);

        $semesterOptions = array_map(function ($semester) {
            $ta_semester = substr($semester['kode'], 2);
            return [
                'value' => $semester['id'],
                'label' => $semester['tahun_ajaran'] . ' (' . $semester['kode'] . ' - ' . strtoupper($semester['jenis']) . ')',
                'ta_semester' => 'TA' . $ta_semester,
                'mk_aktif' => $semester['tahun_ajaran'] . ' ' . ucfirst($semester['jenis']),
            ];
        }, $semesters);

        return $semesterOptions;

    }
    public function filterData($request) {

        $semesterOptions = $this->getSemesters();

        // dd($semesterOptions);
        $courses = $request->session()->get('courses', []);
        $courseDetails = $request->session()->get('courseDetails', []);
        $total_grafik = $request->session()->get('total_grafik');

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

        $data = [
            'semesterOptions' => $semesterOptions,
            'courseDetails' => $paginatedCourses,
            'total_grafik' => $total_grafik
        ];

        return $data;
    }
    public function filterDataPresensi($request) {

        $semesterOptions = $this->getSemesters();

        // dd($semesterOptions);
        $resultpresensiDosen = $request->session()->get('resultpresensiDosen', []);
        $resultPresensiMahasiswa = $request->session()->get('resultPresensiMahasiswa', []);

        $total = count($resultpresensiDosen);
        $perPage = 10;
        $page= $request->query('page', 1);
        $paginatedCourses = new LengthAwarePaginator(
            array_slice($resultpresensiDosen, ($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalMhs = count($resultPresensiMahasiswa);
        $perPageMhs = 10;
        $page= $request->query('page', 1);
        $paginatedCoursesMhs = new LengthAwarePaginator(
            array_slice($resultPresensiMahasiswa, ($page - 1) * $perPageMhs, $perPageMhs),
            $totalMhs,
            $perPageMhs,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $data = [
            'semesterOptions' => $semesterOptions,
            'resultpresensiDosen' => $paginatedCourses,
            'resultPresensiMahasiswa' => $paginatedCoursesMhs,
        ];

        return $data;
    }



    public function index(Request $request) {

        $filterData = $this->filterData($request);

        $semesterOptions = $filterData['semesterOptions'];
        $courseDetails = $filterData['courseDetails'];
        $total_grafik = $filterData['total_grafik'];


        return Inertia::render('Dashboard', [
            'semesterOptions' => $semesterOptions,
            'courseDetails' => $courseDetails,
            'total_grafik' => $total_grafik,
            'shouldRefresh' => session('shouldRefresh', false),
            'title'=> 'Monitoring Statistik Matakuliah'

        ]);



    }



    public function presensi(Request $request) {
        $filterData = $this->filterDataPresensi($request);

        $semesterOptions = $filterData['semesterOptions'];
        $resultpresensiDosen = $filterData['resultpresensiDosen'];
        $resultPresensiMahasiswa = $filterData['resultPresensiMahasiswa'] ?? [];


        return Inertia::render('Presensi', [
            'semesterOptions' => $semesterOptions,
            'resultpresensiDosen' => $resultpresensiDosen,
            'resultPresensiMahasiswa' => $resultPresensiMahasiswa,
            'title'=> 'Monitoring Presensi Matakuliah'


        ]);

    }
    public function nilai(Request $request) {
        $semesterOptions = $this->getSemesters();
        $grades = $request->session()->get('grades', []);
        $total = count($grades);
        $perPage = 10;
        $page= $request->query('page', 1);
        $paginatedCourses = new LengthAwarePaginator(
            array_slice($grades, ($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        return Inertia::render('Nilai', [
            'semesterOptions' => $semesterOptions,
            'title'=> 'Monitoring Nilai Matakuliah',
            'grades' => $paginatedCourses,
            'totalSinkron' => $request->session()->get('totalSinkron', 0),
            'totalTidakSinkron' => $request->session()->get('totalTidakSinkron', 0),
        ]);

    }

    public function filterDataNilai($request) {


        $resultNilai = $request->session()->get('resultNilai', []);



        $data = [
            'resultNilai' => $resultNilai
        ];

        return $data;

    }

    public function log_users(Request $request) {

        $semesterOptions = $this->getSemesters();

        $filterData = $this->filterLogMahasiswa($request);
        $logs = $filterData['logs'];





        return Inertia::render('LogUser', [
            'title'=> 'Log Peserta Matakuliah',
            'semesterOptions' => $semesterOptions,
            'logs' => $logs
        ]);

    }

    public function filterLogMahasiswa($request) {
        $logMahasiswa = $request->session()->get('logMahasiswa', []);

        $total = count($logMahasiswa);
        $perPage = 10;
        $page= $request->query('page', 1);
        $paginatedCourses = new LengthAwarePaginator(
            array_slice($logMahasiswa, ($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $data = [
            'logs' => $paginatedCourses
        ];

        return $data;

    }

}
