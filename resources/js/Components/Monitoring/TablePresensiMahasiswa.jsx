import React, { useMemo } from 'react';
import Pagination from './Pagination';
import moment from 'moment';

const TablePresensiMahasiswa = ({ resultpresensiMahasiswa, queryParams = null }) => {
  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'));
  const namaSemester = savedParams?.selectedSemester.label ?? '';
  const namaProdi = savedParams?.selectedProgram.label ?? '';

  // Jika menggunakan pagination, ambil page dan perPage dari resultpresensiMahasiswa jika ada.
  const page = resultpresensiMahasiswa?.current_page || 1;
  const perPage = resultpresensiMahasiswa?.per_page || 10;

  // Transformasi data: tiap course menghasilkan objek dengan daftar mahasiswas dan sessions.
  const courses = useMemo(() => {
    if (!resultpresensiMahasiswa?.data) return [];
    return resultpresensiMahasiswa.data.map(course => {
      // Kelompokkan sessions berdasarkan minggu menggunakan sessdate (UNIX timestamp)
      const sessionsByWeek = {};
      course.sessions.forEach(session => {
        // Gunakan startOf('isoWeek') agar mendapatkan representasi pekan (misal, Senin pekan tersebut)
        const weekKey = moment.unix(session.sessdate).startOf('isoWeek').format("YYYY-MM-DD");
        if (!sessionsByWeek[weekKey]) {
          sessionsByWeek[weekKey] = [];
        }
        sessionsByWeek[weekKey].push(session);
      });
      // Buat array sessions unik per course (urutkan berdasarkan weekKey)
      const transformedSessions = Object.keys(sessionsByWeek)
        .sort((a, b) => moment(a).diff(moment(b)))
        .map(weekKey => ({
          weekKey,
          tanggal: weekKey,
        }));

      // Transformasi mahasiswa: hitung kehadiran per minggu
      const mahasiswas = course.presensiMahasiswas.map(mhs => {
        const weekCounts = {};
        // Inisialisasi untuk setiap week yang ada pada course
        transformedSessions.forEach(ws => {
          weekCounts[ws.weekKey] = 0;
        });
        mhs.presensi.forEach(record => {
          if (Number(record.status) === 1) {
            // Cari session berdasarkan record.session_id
            const session = course.sessions.find(s => s.id === record.session_id);
            if (session) {
              const weekKey = moment.unix(session.sessdate).startOf('isoWeek').format("YYYY-MM-DD");
              if (weekCounts.hasOwnProperty(weekKey)) {
                weekCounts[weekKey] += 1;
              }
            }
          }
        });
        return {
          nama_mahasiswa: mhs.nama_mahasiswa,
          nim: mhs.nim,
          weekCounts,
        };
      });

      return {
        course_fullname: course.fullname_course,
        kelas_id: course.kelas_id,
        course_id: course.course_id,
        sessions: transformedSessions,
        mahasiswas,
      };
    });
  }, [resultpresensiMahasiswa]);

  // Untuk header, kita gunakan union dari semua weekKey dari seluruh course.
  const weeks = useMemo(() => {
    const weekSet = new Set();
    courses.forEach(course => {
      course.sessions.forEach(ws => weekSet.add(ws.weekKey));
    });
    return Array.from(weekSet).sort((a, b) => moment(a).diff(moment(b)));
  }, [courses]);

  return (
    <div className="collapse collapse-open bg-white shadow-md sm:rounded-lg dark:bg-gray-800 p-4">
      <div className="collapse-title text-xl font-medium">Tabel Presensi Mahasiswa</div>
      <div className="collapse-content">
        <h1 className="text-center font-semibold text-lg">{namaProdi}</h1>
        <h1 className="text-center font-semibold text-lg mb-4">{namaSemester}</h1>
        <table className="table-auto w-full border-collapse">
          <thead className="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th className="text-xs font-medium text-gray-500 uppercase">No</th>
              <th className="text-xs font-medium text-gray-500 uppercase">Nama Kelas</th>
              <th className="text-xs font-medium text-gray-500 uppercase">NIM</th>
              <th className="text-xs font-medium text-gray-500 uppercase">Nama Mahasiswa</th>
              {weeks.map((week, idx) => (
                <th key={idx} className="text-xs text-gray-500 uppercase text-center">
                  {moment(week).format("DD MMM YYYY")}
                </th>
              ))}
              <th className="text-xs font-medium text-gray-500 uppercase text-center">
                Total Kehadiran
              </th>
              <th className="text-xs font-medium text-gray-500 uppercase text-center">
                Persentase
              </th>
            </tr>
          </thead>
          <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            {courses.map((course, courseIndex) =>
              course.mahasiswas.map((mhs, mhsIndex) => {
                // Total sesi untuk course ini
                const totalSessions = course.sessions.length;
                // Total kehadiran mahasiswa: jumlahkan dari setiap minggu (global, jika tidak ada, 0)
                const totalAttendance = weeks.reduce((acc, week) => acc + (mhs.weekCounts[week] || 0), 0);
                // Persentase kehadiran
                const percentage = totalSessions > 0 ? (totalAttendance / totalSessions) * 100 : 0;
                // Badge style: hijau jika >= 80, merah jika < 80
                const badgeStyle =
                  percentage >= 80
                    ? "bg-green-500 text-white px-2 py-1 rounded"
                    : "bg-red-500 text-white px-2 py-1 rounded";
                return (
                  <tr key={`${course.course_id}-${mhsIndex}`}>
                    {mhsIndex === 0 && (
                      <>
                        <td rowSpan={course.mahasiswas.length} className="text-xs text-gray-900" valign="top">
                          {courseIndex + 1 + (page - 1) * perPage}

                        </td>
                        <td rowSpan={course.mahasiswas.length} className="text-xs text-primary" valign="top">
                          <a
                            href={`https://sikola-v2.unhas.ac.id/course/view.php?id=${course.course_id}`}
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            {course.course_fullname}
                          </a>
                        </td>
                      </>
                    )}
                    <td className="text-xs text-gray-900">{mhs.nim}</td>
                    <td className="text-xs text-gray-900">{mhs.nama_mahasiswa}</td>
                    {weeks.map((week, idx) => (
                      <td key={idx} className="text-xs text-center text-gray-900">
                        {mhs.weekCounts[week] || ''}
                      </td>
                    ))}
                    <td className="text-xs text-center text-gray-900">{totalAttendance}</td>
                    <td className="text-xs text-center">
                      <span className={badgeStyle}>
                        {percentage.toFixed(0)}%
                      </span>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
        <div className="pagination mt-4">
          <Pagination links={resultpresensiMahasiswa?.links} queryParams={queryParams} />
        </div>
      </div>
    </div>

  );
};

export default TablePresensiMahasiswa;
