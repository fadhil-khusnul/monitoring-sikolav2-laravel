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
                if (stripos($prodi['nama_resmi'], 'hapus') === false && stripos($prodi['nama_resmi'], 'Testing') === false) {
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
                // Storage::put('public/coursesMatkul/courses_' . $ta_semester . '_' . $selectedProdiId . '_' . $kode_matkul . '.json', json_encode($courses));
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

        return response()->json($semesterOptions);
    }

    public function getProdi(Request $request)
    {
        $baseUrl = env('API_NEOSIA');
        $token = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');
        $selectedSemesterId = $request->query('id_semester');
        $user = $request->user();
        $userFakultasId = $user->fakultas_id;
        $prodiID = $user->prodi_id;

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
                if (stripos($prodi['nama_resmi'], 'hapus') === false && stripos($prodi['nama_resmi'], 'testing') === false) {
                    if ($userFakultasId == 0) {
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

                    if ($userFakultasId > 0 && !$prodiID) {
                        if ($prodi['fakultas']['id'] == $userFakultasId) {
                            # code...
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
                    if($prodiID && $userFakultasId > 0){
                        if ($prodi['id'] == $prodiID) {
                            # code...
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
                    'label' => $mk['nama_matkul'] . ' (' . $mk['kode_matkul'].')',
                    'id' => $mk['id_kelas'],
                    'kode_matkul' => $mk['kode_matkul']
                ];
            }, $matakuliahOptions);
            $matakuliahOptions = array_values($matakuliahOptions);
        }
        return response()->json($matakuliahOptions);

    }


    public function fetchCourses($request, $courses)
    {
        $baseUrl = env('URL_API_SIKOLA');
        $wstoken = env('TOKEN_SIKOLA');
        $ta_semester = $request->query('ta_semester');
        $selectedProdiId = $request->query('id_prodi');
        $kode_dikti = $request->query('kode_dikti');
        $kode_matkul = $request->query('kode_matkul');
        $cekMK = $request->query('cekMK');

        if ($selectedProdiId == 141) {
            $kode_dikti = 'MKUEKSAKTA';
        }
        if ($selectedProdiId == 142) {
            $kode_dikti = 'MKUNONEKSAKTA';
        }


        if ($ta_semester && $selectedProdiId && $kode_dikti) {


            // Cache category lookup based on kode_dikti and semester
            $cacheKeyCategory = "courses_category_{$kode_dikti}_{$ta_semester}";
            $category = \Cache::remember($cacheKeyCategory, now()->addHours(6), function () use ($baseUrl, $wstoken, $kode_dikti) {
                $response = Http::get($baseUrl, [
                    'wstoken' => $wstoken,
                    'moodlewsrestformat' => 'json',
                    'wsfunction' => 'core_course_get_categories',
                    'criteria[0][key]' => 'idnumber',
                    'criteria[0][value]' => $kode_dikti,
                ]);
                return $response->json();
            });

            // If the category was not found, return an empty array
            if (!isset($category[0]['id'])) {
                return [];
            }
            $categoryId = $category[0]['id'];

            // Cache the courses for this category and semester
            $cacheKeyCourses = "courses_by_category_{$categoryId}_{$ta_semester}_{$cekMK}";
            $courses = \Cache::remember($cacheKeyCourses, now()->addHours(6), function () use ($baseUrl, $wstoken, $categoryId, $ta_semester, $cekMK) {
                $response = Http::get($baseUrl, [
                    'wstoken' => $wstoken,
                    'moodlewsrestformat' => 'json',
                    'wsfunction' => 'core_course_get_courses_by_field',
                    'field' => 'category',
                    'value' => $categoryId,
                ]);
                $allCourses = $response->json()['courses'] ?? [];


                if ($cekMK == 'true') {
                    $filtered = array_filter($allCourses, function ($course) use ($ta_semester) {
                        return strpos($course['shortname'], $ta_semester) !== false;
                    });

                }else{
                    $filtered = array_filter($allCourses, function ($course) use ($ta_semester) {
                        return strpos($course['shortname'], $ta_semester) !== false;
                    });
                    $filtered = array_filter($filtered, function ($course) use ($ta_semester) {
                        $kelas_id = explode('.', $course['idnumber'])[1] ?? 0;

                        $filePath = storage_path('app/private/' . $ta_semester . '/' . $kelas_id . '.json');
                        if (!File::exists($filePath)) {
                            return false;
                        }

                        $detailKelas = File::get($filePath);

                        $detailKelas = json_decode($detailKelas, true) ?? null;
                        $id_kelas_kuliah_jenis = $detailKelas['id_kelas_kuliah_jenis'] ?? explode('.', $course['idnumber'])[4] ?? 0;

                        return $id_kelas_kuliah_jenis == 1;
                    });

                }






                return array_values($filtered);
            });


            // If filtering by matakuliah, override courses with matakuliah-specific ones
            if ($kode_matkul) {
                $cacheKeyMatkul = "courses_by_matkul_{$ta_semester}_{$kode_matkul}";
                $courses = \Cache::remember($cacheKeyMatkul, now()->addHours(6), function () use ($baseUrl, $wstoken, $ta_semester, $kode_matkul) {
                    $json = File::get(storage_path('app/private/list_mk_per_kelas_' . $ta_semester . '.json'));
                    $matakuliahKelas = json_decode($json, true);
                    // Filter classes that match the matakuliah code and semester in the shortname
                    $matakuliahKelas = array_filter($matakuliahKelas, function ($mk) use ($kode_matkul, $ta_semester) {
                        return $mk['kode_matkul'] == $kode_matkul && strpos($mk['shortname_sikola'], $ta_semester) !== false;
                    });
                    $matakuliahKelas = array_values($matakuliahKelas);

                    $programCourses = [];
                    foreach ($matakuliahKelas as $kelas) {
                        $cacheKeyKelas = "courses_by_shortname_{$ta_semester}_" . md5($kelas['shortname_sikola']);
                        $response = \Cache::remember($cacheKeyKelas, now()->addHours(6), function () use ($baseUrl, $wstoken, $kelas) {
                            $resp = Http::get($baseUrl, [
                                'wstoken' => $wstoken,
                                'moodlewsrestformat' => 'json',
                                'wsfunction' => 'core_course_get_courses_by_field',
                                'field' => 'shortname',
                                'value' => $kelas['shortname_sikola'],
                            ]);
                            return $resp->json()['courses'] ?? [];
                        });
                        $programCourses = array_merge($programCourses, $response);
                    }
                    return array_values($programCourses);
                });
            }
        }
        return $courses;
    }





    public function getCourses(Request $request)
    {
        $courses = [];
        $filter = $request->query('filter');
        $courseDetails = [];


        $listCourses = $this->fetchCourses($request, $courses);

        $request->session()->put('courses', $listCourses);



        switch ($filter) {
            case 'statistik':
                $getCourseDetails = $this->getCourseDetails($request, $courseDetails);
                $request->session()->put('courseDetails', $getCourseDetails['courses']);
                $request->session()->put('total_grafik', $getCourseDetails['total_grafik']);
                break;
            case 'presensi':
                $getCourseDetails = $this->getPresensi($request, $courseDetails);
                $request->session()->put('resultpresensiDosen', $getCourseDetails['resultpresensiDosen']);
                $request->session()->put('resultPresensiMahasiswa', $getCourseDetails['resultPresensiMahasiswa']);
                break;
            case 'logusers':
                $data = $this->getLogMahasiswa($request);


                $request->session()->put('logMahasiswa', $data);
                break;
            case 'nilai':
                $data = $this->getNilai($request);

                $request->session()->put('grades', $data['grades']);
                $request->session()->put('totalSinkron', $data['totalSinkron']);
                $request->session()->put('totalTidakSinkron', $data['totalTidakSinkron']);
                break;



        }

        return response()->json(['message' => 'success']);


    }
    public function getCourseDetails($request, $courseDetails)
    {

        $courses = $request->session()->get('courses', []);
        $tokenSikola = env('TOKEN_SIKOLA');
        $tokenNeosia = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');

        $baseUrl = env('URL_API_SIKOLA');
        $baseUrlRps = env('URL_RPS_LOGIN');
        $baseUrlRpsMk = env('URL_RPS_MK');
        $urlNeosia = env('API_NEOSIA');

        $response = Http::withoutVerifying()->withOptions(["verify"=>false])->post($baseUrlRps, [
            'username' => env('USER_RPS'),
            'password' => env('PASS_RPS'),
        ]);


        $response = $response->json();
        $tokenRps = $response['access_token'];



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

            $responses = Http::pool(function (Pool $pool) use ($batch, $baseUrl, $tokenSikola, $baseUrlRps, $tokenRps, $urlNeosia, $tokenNeosia, $baseUrlRpsMk) {
                foreach ($batch as $course) {

                    $kelas_id = explode('.', $course['idnumber'])[1] ?? 0;
                    $kode_matkul = null;
                    if (preg_match('/\[(.*?)\]/', $course['fullname'], $matches)) {
                        $kode_matkul = $matches[1];

                    }
                    $pool->as("contents_{$course['id']}")->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_course_get_contents',
                        'courseid' => $course['id'],
                    ]);
                    // $pool->as("users_{$course['id']}")->get($baeUrl, [
                    //     'wstoken' => $tokenSikola,
                    //     'moodlewsrestformat' => 'json',
                    //     'wsfunction' => 'core_enrol_get_enrolled_users',
                    //     'courseid' => $course['id'],
                    // ]);
                    $pool->as("users_{$course['id']}")->withHeaders([
                        'Authorization' => 'Bearer ' . $tokenNeosia,
                    ])->get($urlNeosia.'/admin_mkpk/dosen/input_nilai/kelas_kuliah/'.$kelas_id);
                    $pool->as("rps_{$course['id']}")->withoutVerifying()->withOptions(["verify"=>false])->withHeaders([
                        'Authorization' => 'Bearer ' . $tokenRps,
                    ])->get($baseUrlRpsMk.'/'.$kode_matkul);
                }
            });




            foreach ($batch as $index => $course) {
                $courseId = $course['id'];
                $cacheKey = "course_details_$courseId";

                $courseData = Cache::remember($cacheKey, now()->addHours(6), function () use ($responses, $courseId) {
                    $contentsResponse = $responses["contents_$courseId"] ?? null;
                    $usersResponse = $responses["users_$courseId"] ?? null;
                    $rps = $responses["rps_$courseId"] ?? null;


                    $courseContents = $contentsResponse && $contentsResponse->successful() ? $contentsResponse->json() : [];
                    $enrolledUsers = $usersResponse && $usersResponse->successful() ? $usersResponse->json() : [];
                    $rpsCounts = $rps && $rps->successful() ? $rps->json() : [];

                    return compact('courseContents', 'enrolledUsers', 'rpsCounts');
                });




                $courseContents = $courseData['courseContents'];
                $enrolledUsers = $courseData['enrolledUsers'];

                $rpsCounts = $courseData['rpsCounts'];

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

                if (isset($rpsCounts['rps'])) {
                    $rps = count($rpsCounts['rps']);
                }else{
                    $rps = 0;
                }


                $banyakAlur = collect($courseContents)
                    ->filter(fn($section) => $section['section'] != 0);

                $banyakTerisi = $banyakAlur->filter(fn($section) => isset($section['modules']) && count($section['modules']) > 0);


                // $dosens = collect($enrolledUsers)
                //     ->filter(fn($user) => collect($user['groups'] ?? [])->contains('name', 'DOSEN'))
                //     ->pluck('lastname')
                //     ->join('\n');

                $dosens = $enrolledUsers['kelasKuliah']['dosens'] ?? $enrolledUsers['kelas_kuliah']['dosens'] ?? [];
                $dosens = collect($dosens)->pluck('nama')->join('\n');

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





    public function getPresensi($request, $courseDetails)
    {
        $courses = $request->session()->get('courses', []);

        // dd($courses);
        $tokenSikola = env('TOKEN_SIKOLA');
        $tokenNeosia = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');

        $baseUrl = env('URL_API_SIKOLA');
        $urlNeosia = env('API_NEOSIA');

        $batchSize = 20;
        $batches = array_chunk($courses, $batchSize);

        // Variables to store final results
        $resultpresensiDosen = [];
        $resultpresensiMahasiswa = [];


        foreach ($batches as $batch) {
            // Fetch course data in parallel for each course in this batch.
            $responses = Http::pool(function (Pool $pool) use ($batch, $baseUrl, $tokenSikola, $urlNeosia, $tokenNeosia) {
                foreach ($batch as $course) {
                    $courseId = $course['id'];
                    // Use idnumber to extract kelas_id if needed.
                    $kelas_id = explode('.', $course['idnumber'])[1] ?? 0;
                    // Get course contents (for modules including attendance)
                    $pool->as("contents_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_course_get_contents',
                        'courseid' => $courseId,
                    ]);
                    // Get enrolled users from Moodle
                    $pool->as("users_sikola_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_enrol_get_enrolled_users',
                        'courseid' => $courseId,
                    ]);
                    // Get groups for the course to separate DOSEN and MAHASISWA
                    $pool->as("groups_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_group_get_course_groups',
                        'courseid' => $courseId,
                    ]);
                }
            });

            foreach ($batch as $course) {
                $courseId = $course['id'];
                $cacheKey = "course_presensi_$courseId";

                // Cache the combined course data (contents, enrolled users, groups)
                $courseData = \Cache::remember($cacheKey, now()->addHours(6), function () use ($responses, $courseId) {
                    $contentsResponse = $responses["contents_{$courseId}"] ?? null;
                    $usersResponse    = $responses["users_sikola_{$courseId}"] ?? null;
                    $groupResponse    = $responses["groups_{$courseId}"] ?? null;

                    $courseContents = ($contentsResponse && $contentsResponse->successful()) ? $contentsResponse->json() : [];
                    $enrolledUsers  = ($usersResponse && $usersResponse->successful())    ? $usersResponse->json()    : [];
                    $groupsCourse   = ($groupResponse && $groupResponse->successful())    ? $groupResponse->json()     : [];

                    return compact('courseContents', 'enrolledUsers', 'groupsCourse');
                });



                // Get attendance modules from courseContents
                $attendanceModules = collect($courseData['courseContents'])
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'attendance')
                    ->values()
                    ->toArray();

                if (empty($attendanceModules)) {
                    continue;
                }

                // Use the first attendance module instance
                $attendanceInstance = $attendanceModules[0]['instance'] ?? null;
                if (!$attendanceInstance) {
                    continue;
                }

                // Cache the sessions result for this course
                $cacheKeySessions = "attendance_sessions_{$courseId}_{$attendanceInstance}";
                $sessions = \Cache::remember($cacheKeySessions, now()->addHours(6), function () use ($baseUrl, $tokenSikola, $attendanceInstance) {
                    $sessionResponse = Http::pool(function (Pool $pool) use ($baseUrl, $tokenSikola, $attendanceInstance) {
                        $pool->as("session")->get($baseUrl, [
                            'wstoken' => $tokenSikola,
                            'moodlewsrestformat' => 'json',
                            'wsfunction' => 'mod_attendance_get_sessions',
                            'attendanceid' => $attendanceInstance,
                        ]);
                    });
                    return (isset($sessionResponse['session']) && $sessionResponse['session']->ok())
                        ? $sessionResponse['session']->json()
                        : [];
                });


                // If sessions is empty, skip
                if (!count($sessions)) {
                    continue;
                }




                // Separate sessions for dosen and mahasiswa based on group.
                if (isset($courseData['groupsCourse']) && count($sessions) > 0) {
                    $groupsCourse = $courseData['groupsCourse'];
                    $groupDosen = collect($groupsCourse)->firstWhere('name', 'DOSEN');
                    $groupMahasiswa = collect($groupsCourse)->firstWhere('name', 'MAHASISWA');

                    // Filter sessions by groupid.
                    $sessionDosen = collect($sessions)
                        ->filter(fn($session) => isset($groupDosen['id']) && $session['groupid'] == $groupDosen['id'])
                        ->values()
                        ->toArray();
                    $sessionMahasiswa = collect($sessions)
                        ->filter(fn($session) => isset($groupMahasiswa['id']) && $session['groupid'] == $groupMahasiswa['id'])
                        ->values()
                        ->toArray();

                    // For dosen, filter enrolled users that belong to group DOSEN.
                    $dosenList = collect($courseData['enrolledUsers'])
                        ->filter(fn($user) => collect($user['groups'] ?? [])->contains('name', 'DOSEN'))
                        ->values()
                        ->toArray();
                    // Similarly for mahasiswa.
                    $mahasiswaList = collect($courseData['enrolledUsers'])
                        ->filter(fn($user) => collect($user['groups'] ?? [])->contains('name', 'MAHASISWA'))
                        ->values()
                        ->toArray();

                    // Map presensi for each dosen.
                    $presensDosens = collect($dosenList)->map(function ($dosen) use ($sessionDosen) {
                        $presensi = collect($sessionDosen)->map(function ($session) use ($dosen) {
                            $log = collect($session['attendance_log'] ?? [])
                                ->firstWhere('studentid', $dosen['id']);
                            return [
                                'session_id' => $session['id'] ?? null,
                                'sessdate'   => isset($session['sessdate']) ? $session['sessdate'] : null,
                                'status'     => $log ? 1 : 0,
                            ];
                        })->values()->all();
                        return [
                            'nama_dosen' => $dosen['lastname'] ?? 'Unknown',
                            'presensi'   => $presensi,
                        ];
                    })->values()->all();

                    // Map presensi for each mahasiswa.
                    $presensMahasiswas = collect($mahasiswaList)->map(function ($mhs) use ($sessionMahasiswa) {
                        $presensi = collect($sessionMahasiswa)->map(function ($session) use ($mhs) {
                            $log = collect($session['attendance_log'] ?? [])
                                ->firstWhere('studentid', $mhs['id']);
                            return [
                                'session_id' => $session['id'] ?? null,
                                'sessdate'   => isset($session['sessdate']) ? $session['sessdate'] : null,
                                'status'     => $log ? 1 : 0,
                            ];
                        })->values()->all();
                        return [
                            'nama_mahasiswa' => $mhs['lastname'] ?? 'Unknown',
                            'nim' => $mhs['firstname'] ?? '',
                            'presensi'       => $presensi,
                        ];
                    })->values()->all();

                    $resultpresensiDosen[] = [
                        'fullname_course' => $course['fullname'],
                        'kelas_id'        => $course['idnumber'] ?? '',
                        'course_id'       => $course['id'],
                        'sessions'        => $sessionDosen,
                        'presensDosens'   => $presensDosens,
                    ];
                    $resultpresensiMahasiswa[] = [
                        'fullname_course'      => $course['fullname'],
                        'kelas_id'             => $course['idnumber'] ?? '',
                        'course_id'            => $course['id'],
                        'sessions'             => $sessionMahasiswa,
                        'presensiMahasiswas'   => $presensMahasiswas,
                    ];
                }
            }
        }

        $data = [
            'resultpresensiDosen'     => $resultpresensiDosen,
            'resultPresensiMahasiswa' => $resultpresensiMahasiswa,
        ];

        return $data;
    }




    public function getLogMahasiswa($request)
    {
        $courses = $request->session()->get('courses', []);
        $tokenSikola = env('TOKEN_SIKOLA');
        $tokenNeosia = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');
        $baseUrl = env('URL_API_SIKOLA');
        $urlNeosia = env('API_NEOSIA');
        $tokenLog = env('TOKEN_LOG');
        $batchSize = 20;
        $batches = array_chunk($courses, $batchSize);
        $dataLog = [];

        foreach ($batches as $batch) {

            $responses = Http::pool(function (Pool $pool) use ($batch, $baseUrl, $tokenSikola, $urlNeosia, $tokenLog, $tokenNeosia) {
                foreach ($batch as $course) {
                    $courseId = $course['id'];
                    $kelas_id = explode('.', $course['idnumber'])[1] ?? 0;


                    $pool->as("logMahasiswa_{$courseId}")->withToken($tokenLog)
                    ->get('https://sikola-v2.unhas.ac.id/report/getLogMhs.php', [
                        'courseId' => $courseId,
                    ]);

                    $pool->as("mahasiswas_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_enrol_get_enrolled_users',
                        'courseid' => $courseId,
                    ]);

                    $pool->as("contents_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_course_get_contents',
                        'courseid' => $courseId,
                    ]);

                    $pool->as("groups_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'core_group_get_course_groups',
                        'courseid' => $courseId,
                    ]);




                }
            });

            foreach ($batch as $course) {

                $courseId = $course['id'];
                $cacheKey = "course_logMahasiswa_$courseId";

                $courseData = \Cache::remember($cacheKey, now()->addHours(6), function () use ($responses, $courseId) {
                    $logResponse = $responses["logMahasiswa_{$courseId}"] ?? null;
                    $mhsRes = $responses["mahasiswas_{$courseId}"] ?? null;
                    $contentsResponse = $responses["contents_{$courseId}"] ?? null;
                    $groupResponse    = $responses["groups_{$courseId}"] ?? null;


                    $courseContents = ($contentsResponse && $contentsResponse->successful()) ? $contentsResponse->json() : [];
                    $logMhs = ($logResponse && $logResponse->successful()) ? $logResponse->json() : [];
                    $enrolledUsers = ($mhsRes && $mhsRes->successful()) ? $mhsRes->json() : [];
                    $groupCourse   = ($groupResponse && $groupResponse->successful()) ? $groupResponse->json()     : [];

                    return compact('logMhs', 'enrolledUsers', 'courseContents', 'groupCourse');
                });

                $attendanceModules = collect($courseData['courseContents'])
                    ->flatMap(fn($section) => $section['modules'] ?? [])
                    ->filter(fn($module) => $module['modname'] === 'attendance')
                    ->values()
                    ->toArray();

                if (empty($attendanceModules)) {
                    continue;
                }

                // Use the first attendance module instance
                $attendanceInstance = $attendanceModules[0]['instance'] ?? null;
                if (!$attendanceInstance) {
                    continue;
                }

                // Cache the sessions result for this course
                $cacheKeySessions = "attendance_sessions_{$courseId}_{$attendanceInstance}";
                $sessions = \Cache::remember($cacheKeySessions, now()->addHours(6), function () use ($baseUrl, $tokenSikola, $attendanceInstance) {
                    $sessionResponse = Http::pool(function (Pool $pool) use ($baseUrl, $tokenSikola, $attendanceInstance) {
                        $pool->as("session")->get($baseUrl, [
                            'wstoken' => $tokenSikola,
                            'moodlewsrestformat' => 'json',
                            'wsfunction' => 'mod_attendance_get_sessions',
                            'attendanceid' => $attendanceInstance,
                        ]);
                    });
                    return (isset($sessionResponse['session']) && $sessionResponse['session']->ok())
                        ? $sessionResponse['session']->json()
                        : [];
                });


                // If sessions is empty, skip
                if (!count($sessions)) {
                    continue;
                }


                if (isset($courseData['groupCourse']) && count($sessions) > 0) {
                    $logMhs = $courseData['logMhs'];

                    $mahasiswaList = collect($courseData['enrolledUsers'])
                            ->filter(fn($user) => collect($user['groups'] ?? [])->contains('name', 'MAHASISWA'))
                            ->values()
                            ->toArray();

                    $logsMahasiswa = collect($mahasiswaList)->map(function ($mhs) use ($logMhs) {
                        $logs = collect($logMhs)->filter(fn($log) => $log['userid'] == $mhs['id'])->values()->all();
                        return [
                            'nama_mahasiswa' => $mhs['lastname'] ?? 'Unknown',
                            'nim' => $mhs['firstname'] ?? '',
                            'logs'       => $logs,
                        ];
                    })->values()->all();

                    $dosenList = collect($courseData['enrolledUsers'])
                    ->filter(fn($user) => collect($user['groups'] ?? [])->contains('name', 'DOSEN'))
                    ->values()
                    ->toArray();
                    $logsDosen = collect($dosenList)->map(function ($dosen) use ($logMhs) {
                        $logs = collect($logMhs)->filter(fn($log) => $log['userid'] == $dosen['id'])->values()->all();
                        return [
                            'nama_dosen' => $dosen['lastname'] ?? 'N/A',
                            'nip' => $dosen['username'] ?? 'N/A',
                            'logs'       => $logs,
                        ];
                    })->values()->all();

                    $groupMahasiswa = collect($courseData['groupCourse'])->firstWhere('name', 'MAHASISWA');
                    $sessionMahasiswa = collect($sessions)
                    ->filter(fn($session) => isset($groupMahasiswa['id']) && $session['groupid'] == $groupMahasiswa['id'])
                    ->values()
                    ->toArray();

                    $groupDosen = collect($courseData['groupCourse'])->firstWhere('name', 'DOSEN');
                    $sessionDosen = collect($sessions)
                    ->filter(fn($session) => isset($groupDosen['id']) && $session['groupid'] == $groupDosen['id'])
                    ->values()
                    ->toArray();

                    $dataLog[] = [
                        'fullname_course' => $course['fullname'],
                        'course_id'       => $course['id'],
                        'sessionMahasiswa' => $sessionMahasiswa,
                        'logsMahasiswa'   => $logsMahasiswa,
                        'sessionDosen' => $sessionDosen,
                        'logsDosen'=> $logsDosen
                    ];
                }




            }

        }

        return $dataLog;

    }
    public function getNilai($request)
    {
        $courses = $request->session()->get('courses', []);

        $tokenSikola = env('TOKEN_SIKOLA');
        $tokenNeosia = Cookie::get('access_token') ?? env('TOKEN_NEOSIA');

        $baseUrl = env('URL_API_SIKOLA');
        $urlNeosia = env('API_NEOSIA');
        $tokenNilai = env('TOKEN_NILAI');

        $batchSize = 20;
        $batches = array_chunk($courses, $batchSize);

        $dataNilai = [];
        $totalSinkron = 0;
        $totalTidakSinkron = 0;
        foreach ($batches as $batch) {
            $responses = Http::pool(function (Pool $pool) use ($batch, $baseUrl, $tokenSikola, $urlNeosia, $tokenNilai, $tokenNeosia) {
                foreach ($batch as $course) {
                    $courseId = $course['id'];
                    // Misal: idnumber berformat "SOMETHING.1" untuk kelas
                    $kelas_id = explode('.', $course['idnumber'])[1] ?? 0;

                    // Request konten (untuk mendapatkan modul attendance)
                    $pool->as("nilaisinkron_{$courseId}")->withToken($tokenNilai)
                    ->get('https://sikola-v2.unhas.ac.id/grade/getSinkronNilai.php', [
                        'courseId' => $courseId,
                    ]);
                    $pool->as("nilaiMahasiswa_{$courseId}")->retry(3, 200)->get($baseUrl, [
                        'wstoken' => $tokenSikola,
                        'moodlewsrestformat' => 'json',
                        'wsfunction' => 'gradereport_user_get_grade_items',
                        'courseid' => $courseId,
                    ]);

                    $pool->as("dosens_{$course['id']}")->withHeaders([
                        'Authorization' => 'Bearer ' . $tokenNeosia,
                    ])->get($urlNeosia.'/admin_mkpk/dosen/input_nilai/kelas_kuliah/'.$kelas_id);
                }
            });

            foreach ($batch as $course) {

                $courseId = $course['id'];
                $cacheKey = "course_nilaiSinkron_$courseId";

                $courseData = \Cache::remember($cacheKey, now()->addHours(6), function () use ($responses, $courseId) {
                    $nilaiResponse = $responses["nilaisinkron_{$courseId}"] ?? null;
                    $nilaiMhsResponse = $responses["nilaiMahasiswa_{$courseId}"] ?? null;
                    $usersResponse = $responses["dosens_$courseId"] ?? null;



                    $gradeCourse = ($nilaiResponse && $nilaiResponse->successful()) ? $nilaiResponse->json() : [];
                    $gradeMahasiswa = ($nilaiMhsResponse && $nilaiMhsResponse->successful()) ? $nilaiMhsResponse->json() : [];

                    $enrolledUsers = $usersResponse && $usersResponse->successful() ? $usersResponse->json() : [];

                    return compact('gradeCourse', 'gradeMahasiswa', 'enrolledUsers');
                });
                if (isset($courseData['gradeCourse'][0]['status']) && $courseData['gradeCourse'][0]['status'] == 1) {
                    $totalSinkron += 1;
                }else{
                    $totalTidakSinkron += 1;
                }

                $enrolledUsers = $courseData['enrolledUsers'];

                $dosens = $enrolledUsers['kelasKuliah']['dosens'] ?? $enrolledUsers['kelas_kuliah']['dosens'] ?? [];
                $dosens = collect($dosens)->pluck('nama')->join('\n');



                $dataNilai[] = [
                    'id' => $courseId,
                    'fullname' => $course['fullname'],
                    'status' => $courseData['gradeCourse'][0]['status'] ?? null,
                    'nilaiMahasiswa' => $courseData['gradeMahasiswa']['usergrades'] ?? [],
                    'gradeItems' => $courseData['gradeMahasiswa']['usergrades'][0]['gradeitems'] ?? [],
                    'dosens'=> $dosens

                ];


            }

        }

        $data = [
            'grades' => $dataNilai,
            'totalSinkron' => $totalSinkron,
            'totalTidakSinkron' => $totalTidakSinkron,
        ];

        return $data;


    }
}
