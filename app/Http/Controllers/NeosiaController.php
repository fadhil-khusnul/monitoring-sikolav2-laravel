<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class NeosiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function semester(Request $request)
    {
        $baseUrl = env('API_NEOSIA');
        $user = $request->user();
        $userFakultasId = $user->fakultas_id;
        $token = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');





        // Fetch semesters
        $semesterResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get($baseUrl . '/admin_mkpk/semester');

        $semesters = $semesterResponse->json()['semesters'];

        // Transform the semesters data to match the format expected by the Select component
        $semesterOptions = array_map(function ($semester) {
            $ta_semester = substr($semester['kode'], 2);
            return [
                'value' => $semester['id'],
                'label' => $semester['tahun_ajaran'] . ' (' . $semester['kode'] . ' - ' . strtoupper($semester['jenis']) . ')',
                'ta_semester' => 'TA' . $ta_semester,
                'mk_aktif' => $semester['tahun_ajaran'] . ' ' . ucfirst($semester['jenis']),
            ];
        }, $semesters);

        // Fetch prodi based on selected semester
        $selectedSemesterId = $request->query('id_semester');
        $prodiOptions = [];


        if ($selectedSemesterId) {
            $prodiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get($baseUrl . '/admin_mkpk/prodi_semester', [
                'filters' => [
                    'id_semester' => $selectedSemesterId,
                ],
            ]);

            $prodiSemesters = $prodiResponse->json()['prodiSemesters'];

            // Fetch all prodi to map faculty data
            $allProdiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($baseUrl . '/admin_mkpk/prodi');

            $allProdiList = $allProdiResponse->json()['prodis'];
            $prodiMap = [];
            foreach ($allProdiList as $prodi) {
                $prodiMap[$prodi['id']] = $prodi;
            }

            // Filter and transform the prodi data to match the format expected by the Select component
            foreach ($prodiSemesters as $prodiSemester) {
                $prodi = $prodiMap[$prodiSemester['id_prodi']];
                if (stripos($prodi['nama_resmi'], 'hapus') === false) {
                    if ($userFakultasId == 0 || $prodi['fakultas']['id'] == $userFakultasId) {
                        $facultyName = $prodi['fakultas']['nama_resmi'];
                        if (!isset($prodiOptions[$facultyName])) {
                            $prodiOptions[$facultyName] = [
                                'label' => $facultyName,
                                'options' => []
                            ];
                        }
                        $prodiOptions[$facultyName]['options'][] = [
                            'value' => $prodi['id'],
                            'label' => $prodi['nama_resmi'],
                            'data_fakultas' => $facultyName,
                            'kode_dikti' => $prodi['kode_dikti'],
                        ];
                    }
                }
            }

            // Convert associative array to indexed array for Select component
            $prodiOptions = array_values($prodiOptions);
        }

        $ta_semester = $request->query('ta_semester');
        $selectedProdiId = $request->query('id_prodi');
        $kode_dikti = $request->query('kode_dikti');
        $matakuliahOptions = [];
        $courses = [];
        if ($ta_semester && $selectedProdiId && $kode_dikti) {
            $json = File::get(storage_path('app/private/list_mk_' . $ta_semester . '.json'));
            $matakuliahOptions = json_decode($json, true);
            $matakuliahOptions = array_filter($matakuliahOptions, function ($mk) use ($selectedProdiId) {
                return $mk['id_prodi'] == $selectedProdiId;
            });

            $matakuliahOptions = array_map(function ($mk) {
                return [
                    'value' => $mk['id_kelas'],
                    'label' => $mk['fullname_sikola'],
                    'id' => $mk['id_kelas'],
                    'kode_matkul' => $mk['kode_matkul']
                ];
            }, $matakuliahOptions);
            $matakuliahOptions = array_values($matakuliahOptions);

            //GET COURSE SIKOLA
            $baseUrl = env('URL_API_SIKOLA');
            $wstoken = env('TOKEN_SIKOLA');
            $response = Http::get($baseUrl, [
                'wstoken' => $wstoken,
                'moodlewsrestformat' => 'json',
                'wsfunction' => 'core_course_get_categories',
                'criteria[0][key]' => 'idnumber',
                'criteria[0][value]' => $kode_dikti,
            ]);
            $category = $response->json();

            $response = Http::get($baseUrl, [
                'wstoken' => $wstoken,
                'moodlewsrestformat' => 'json',
                'wsfunction' => 'core_course_get_courses_by_field',
                'field' => 'category',
                'value' => $category[0]['id'],
            ]);

            $courses = $response->json()['courses'];
            $courses = array_filter($courses, function ($course) use ($ta_semester) {
                return strpos($course['shortname'], $ta_semester) !== false;
            });
            $courses = array_values($courses);
            Storage::put('public/coursesProdi/courses_' . $ta_semester . '_' . $selectedProdiId . '_' . $kode_matkul . '.json', json_encode($courses));

            $kode_matkul = $request->query('kode_matkul');

            if ($kode_matkul) {
                $json = File::get(storage_path('app/private/list_mk_per_kelas_' . $ta_semester . '.json'));
                $matakuliahKelas = json_decode($json, true);

                $matakuliahKelas = array_filter($matakuliahKelas, function ($mk) use ($kode_matkul, $ta_semester) {
                    return $mk['kode_matkul'] == $kode_matkul && strpos($mk['shortname_sikola'], $ta_semester) !== false;
                });

                $matakuliahKelas = array_values($matakuliahKelas);

                $courses = [];

                foreach ($matakuliahKelas as $kelas) {
                    $response = Http::get($baseUrl, [
                        'wstoken' => $wstoken,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_course_get_courses_by_field',
                        'field' => 'shortname',
                        'value' => $kelas['shortname_sikola'],
                    ]);

                    $responseKelas = $response->json()['courses'];
                    $courses = array_merge($courses, $responseKelas);



                }

                $courses = array_values($courses);
                Storage::put('public/coursesMatkul/courses_' . $ta_semester . '_' . $selectedProdiId . '_' . $kode_matkul . '.json', json_encode($courses));
            }

            // $perPage = 10;
            // $page = $request->query('page', 1);
            // $offset = ($page - 1) * $perPage;
            // $paginatedCourses = new LengthAwarePaginator(
            //     array_slice($courses, $offset, $perPage),
            //     count($courses),
            //     $perPage,
            //     $page,
            //     ['path' => $request->url(), 'query' => $request->query()]
            // );


        }


        return Inertia::render('Dashboard', [
            'semesterOptions' => $semesterOptions,
            'programStudiOptions' => $prodiOptions,
            'matakuliahOptions' => $matakuliahOptions,
            'selectedSemester' => $selectedSemesterId,
            'selectedProgramStudi' => $selectedProdiId,
            // 'courses' => $courses,

        ]);
    }

    public function getSemesters(Request $request)
    {
        $baseUrl = env('API_NEOSIA');
        $token = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');

        $semesterResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->withOptions([
            'verify' => false,
        ])->timeout(60)->retry(3, 100)->get($baseUrl . '/admin_mkpk/semester');

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

        return response()->json($semesterOptions);
    }

    public function getProdi(Request $request)
    {
        $baseUrl = env('API_NEOSIA');
        $token = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');
        $selectedSemesterId = $request->query('id_semester');
        $user = $request->user();
        $userFakultasId = $user->fakultas_id;

        $prodiOptions = [];

        // dd($selectedSemesterId);

        if ($selectedSemesterId) {
            $prodiResponse = Http::withToken($token)->get($baseUrl . '/admin_mkpk/prodi_semester', [
                'filters' => [
                    'id_semester' => $selectedSemesterId,
                ],
            ]);

            $prodiSemesters = $prodiResponse->json()['prodiSemesters'];

            $allProdiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($baseUrl . '/admin_mkpk/prodi');

            $allProdiList = $allProdiResponse->json()['prodis'];
            $prodiMap = [];
            foreach ($allProdiList as $prodi) {
                $prodiMap[$prodi['id']] = $prodi;
            }

            foreach ($prodiSemesters as $prodiSemester) {
                $prodi = $prodiMap[$prodiSemester['id_prodi']];
                if (stripos($prodi['nama_resmi'], 'hapus') === false) {
                    if ($userFakultasId == 0 || $prodi['fakultas']['id'] == $userFakultasId) {
                        $facultyName = $prodi['fakultas']['nama_resmi'];
                        if (!isset($prodiOptions[$facultyName])) {
                            $prodiOptions[$facultyName] = [
                                'label' => $facultyName,
                                'options' => []
                            ];
                        }
                        $prodiOptions[$facultyName]['options'][] = [
                            'value' => $prodi['id'],
                            'label' => $prodi['nama_resmi'],
                            'data_fakultas' => $facultyName,
                            'kode_dikti' => $prodi['kode_dikti'],
                        ];
                    }
                }
            }

            $prodiOptions = array_values($prodiOptions);
        }

        return response()->json($prodiOptions);
    }

    public function getMatkul(Request $request) {

        $ta_semester = $request->query('ta_semester');
        $selectedProdiId = $request->query('id_prodi');
        $kode_dikti = $request->query('kode_dikti');
        $matakuliahOptions = [];

        if ($ta_semester && $selectedProdiId && $kode_dikti) {
            $json = File::get(storage_path('app/private/list_mk_' . $ta_semester . '.json'));
            $matakuliahOptions = json_decode($json, true);
            $matakuliahOptions = array_filter($matakuliahOptions, function ($mk) use ($selectedProdiId) {
                return $mk['id_prodi'] == $selectedProdiId;
            });

            $matakuliahOptions = array_map(function ($mk) {
                return [
                    'value' => $mk['id_kelas'],
                    'label' => $mk['fullname_sikola'],
                    'id' => $mk['id_kelas'],
                    'kode_matkul' => $mk['kode_matkul']
                ];
            }, $matakuliahOptions);
            $matakuliahOptions = array_values($matakuliahOptions);
        }
        return response()->json($matakuliahOptions);

    }





    public function getCourses(Request $request)
    {
        // dd($request);
        $baseUrl = env('URL_API_SIKOLA');
        $wstoken = env('TOKEN_SIKOLA');
        $ta_semester = $request->query('ta_semester');
        $selectedProdiId = $request->query('id_prodi');
        $kode_dikti = $request->query('kode_dikti');
        $kode_matkul = $request->query('kode_matkul');

        $courses = [];

        if ($ta_semester && $selectedProdiId && $kode_dikti) {
            $response = Http::get($baseUrl, [
                'wstoken' => $wstoken,
                'moodlewsrestformat' => 'json',
                'wsfunction' => 'core_course_get_categories',
                'criteria[0][key]' => 'idnumber',
                'criteria[0][value]' => $kode_dikti,
            ]);
            $category = $response->json();

            $response = Http::get($baseUrl, [
                'wstoken' => $wstoken,
                'moodlewsrestformat' => 'json',
                'wsfunction' => 'core_course_get_courses_by_field',
                'field' => 'category',
                'value' => $category[0]['id'],
            ]);

            $courses = $response->json()['courses'];
            $courses = array_filter($courses, function ($course) use ($ta_semester) {
                return strpos($course['shortname'], $ta_semester) !== false;
            });
            $courses = array_values($courses);

            if ($kode_matkul) {

                // dd($kode_matkul);
                $json = File::get(storage_path('app/private/list_mk_per_kelas_' . $ta_semester . '.json'));
                $matakuliahKelas = json_decode($json, true);

                $matakuliahKelas = array_filter($matakuliahKelas, function ($mk) use ($kode_matkul, $ta_semester) {
                    return $mk['kode_matkul'] == $kode_matkul && strpos($mk['shortname_sikola'], $ta_semester) !== false;
                });

                $matakuliahKelas = array_values($matakuliahKelas);

                $courses = [];

                foreach ($matakuliahKelas as $kelas) {
                    $response = Http::get($baseUrl, [
                        'wstoken' => $wstoken,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_course_get_courses_by_field',
                        'field' => 'shortname',
                        'value' => $kelas['shortname_sikola'],
                    ]);

                    $responseKelas = $response->json()['courses'];
                    $courses = array_merge($courses, $responseKelas);
                }

                $courses = array_values($courses);
            }

            Storage::put('public/coursesMatkul/courses_' . $ta_semester . '_' . $selectedProdiId . '_' . $kode_matkul . '.json', json_encode($courses));
        }


        $request->session()->put('courses', $courses);

        $courseDetails= [];

        $getCourseDetails = $this->getCourseDetails($request, $courseDetails);

        $request->session()->put('courseDetails', $getCourseDetails['courses']);
        $request->session()->put('total_grafik', $getCourseDetails['total_grafik']);


        return response()->json(['message' => 'success']);


    }
    public function getCourseDetails($request, $courseDetails)
    {

        $courses = $request->session()->get('courses', []);
        $tokenSikola = env('TOKEN_SIKOLA');
        $baseUrl = 'https://sikola-v2.unhas.ac.id/webservice/rest/server.php';

        $batchSize = 20;
        $batches = array_chunk($courses, $batchSize);

        $totalBanyakTerisi = 0;
        $totalRps = 0;
        $totalTugas = 0;
        $totalDoc = 0;
        $totalSurvey = 0;
        $totalQuiz = 0;
        $totalForum = 0;

        foreach ($batches as $batch) {

            $responses = Http::pool(function (Pool $pool) use ($batch, $baseUrl, $tokenSikola) {
                foreach ($batch as $course) {
                    $pool->as("contents_{$course['id']}")->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_course_get_contents',
                        'courseid' => $course['id'],
                    ]);
                    $pool->as("users_{$course['id']}")->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_enrol_get_enrolled_users',
                        'courseid' => $course['id'],
                    ]);
                }
            });
            foreach ($batch as $index => $course) {
                $courseId = $course['id'];
                $cacheKey = "course_details_$courseId";

                $courseData = Cache::remember($cacheKey, now()->addHours(6), function () use ($responses, $courseId) {
                    $contentsResponse = $responses["contents_$courseId"] ?? null;
                    $usersResponse = $responses["users_$courseId"] ?? null;


                    $courseContents = $contentsResponse && $contentsResponse->successful() ? $contentsResponse->json() : [];
                    $enrolledUsers = $usersResponse && $usersResponse->successful() ? $usersResponse->json() : [];

                    return compact('courseContents', 'enrolledUsers');
                });
                // $courseData = Cache::remember($cacheKey, now()->addHours(6), function () use ($baseUrl, $tokenSikola, $courseId) {
                //     $responses = Http::pool(fn (Pool $pool) => [
                //         $pool->as("contents")->get($baseUrl,[
                //             'wstoken' => $tokenSikola,
                //             'moodlewsrestformat' => 'json',
                //             'wsfunction' => 'core_course_get_contents',
                //             'courseid' => $courseId,
                //         ]),
                //         $pool->as("users")->get($baseUrl,[
                //             'wstoken' => $tokenSikola,
                //             'moodlewsrestformat' => 'json',
                //             'wsfunction' => 'core_enrol_get_enrolled_users',
                //             'courseid' => $courseId,
                //         ]),

                //     ]);

                //     $courseContents = $responses["contents"]->json();
                //     $enrolledUsers = $responses["users"]->json();
                //     return compact('courseContents', 'enrolledUsers');
                // });



                $courseContents = $courseData['courseContents'];
                $enrolledUsers = $courseData['enrolledUsers'];

                // Process details
                $urls = collect($courseContents)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'url')
                    ->count();

                $files = collect($courseContents)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => in_array($module['modname'], ['resource', 'folder']))
                    ->count();

                $forums = collect($courseContents)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'forum')
                    ->count();

                $tugas = collect($courseContents)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'assign')
                    ->count();

                $surveys = collect($courseContents)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'survey')
                    ->count();

                $quizes = collect($courseContents)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'quiz')
                    ->count();

                $rps = collect($courseContents)
                    ->filter(fn($section) => $section['section'] == 0)
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'resource')
                    ->count();

                $banyakAlur = collect($courseContents)
                    ->filter(fn($section) => $section['section'] != 0);

                $banyakTerisi = $banyakAlur->filter(fn($section) => isset($section['modules']) && count($section['modules']) > 0);

                $dosens = collect($enrolledUsers)
                    ->filter(fn($user) => collect($user['groups'] ?? [])->contains('name', 'DOSEN'))
                    ->pluck('lastname')
                    ->join('\n');

                $totalBanyakTerisi += $banyakTerisi->count();
                $totalRps += $rps;
                $totalTugas += $tugas;
                $totalDoc += $files;
                $totalSurvey += $surveys;
                $totalQuiz += $quizes;
                $totalForum += $forums;

                $courseDetails[] = [
                    'id' => $courseId,
                    'fullname' => $course['fullname'],
                    'totalDocs' => $files,
                    'totalCases' => $urls,
                    'totalRPS' => $rps,
                    'totalTugas' => $tugas,
                    'totalSurvey' => $surveys,
                    'totalQuiz' => $quizes,
                    'totalForum' => $forums,
                    'totalBanyakTerisi' => $banyakTerisi->count(),
                    'totalBanyakAlur' => $banyakAlur->count(),
                    'dosens' => $dosens,
                ];
            }
        }

        $data = [
            'courses' => $courseDetails,
            'total_grafik' => [
                'totalBanyakTerisi' => $totalBanyakTerisi,
                'totalRps' => $totalRps,
                'totalTugas' => $totalTugas,
                'totalDoc' => $totalDoc,
                'totalSurvey' => $totalSurvey,
                'totalQuiz' => $totalQuiz,
                'totalForum' => $totalForum,
            ]

        ];


        return $data;

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
