import React, { useEffect, useMemo, useState } from 'react';
import {
  useQuery,

} from '@tanstack/react-query'
import Pagination from './Pagination';

import moment from 'moment';


const TablePresensi = ({ resultpresensiDosen, queryParams = null }) => {

  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'))
  const namaSemester = savedParams?.selectedSemester.label ?? ''
  const namaProdi = savedParams?.selectedProgram.label ?? ''



  const page = resultpresensiDosen?.current_page
  const perPage = resultpresensiDosen?.per_page

  console.log(resultpresensiDosen);

  const courses = useMemo(() => {
    if (!resultpresensiDosen?.data) return [];
    return resultpresensiDosen.data.map(course => {
      // Kelompokkan session berdasarkan minggu (gunakan startOf('isoWeek'))
      const sessionsByWeek = {};
      course.sessions.forEach(session => {
        const weekKey = moment.unix(session.sessdate).startOf('isoWeek').format("YYYY-MM-DD");
        if (!sessionsByWeek[weekKey]) {
          sessionsByWeek[weekKey] = [];
        }
        sessionsByWeek[weekKey].push(session);
      });
      // Buat array sessions (unik per minggu)
      const transformedSessions = Object.keys(sessionsByWeek)
        .sort((a, b) => moment(a).diff(moment(b)))
        .map(weekKey => ({
          weekKey,
          // Kita gunakan weekKey sebagai representasi tanggal pekan (misalnya, Senin pekan tersebut)
          tanggal: weekKey,
        }));

      // Transformasi dosen: jumlahkan kehadiran per minggu
      const dosens = course.presensDosens.map(dosen => {
        // Inisialisasi attendance count per minggu (sesuai dengan sessions yang ada pada course)
        const weekCounts = {};
        transformedSessions.forEach(ws => {
          weekCounts[ws.weekKey] = 0;
        });
        dosen.presensi.forEach(record => {
          if (Number(record.status) === 1) {
            // Cari session dari course.sessions berdasarkan record.session_id
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
          nama_dosen: dosen.nama_dosen,
          weekCounts,
        };
      });

      return {
        course_fullname: course.fullname_course,
        kelas_id: course.kelas_id,
        course_id: course.course_id,
        sessions: transformedSessions,
        dosens,
      };
    });
  }, [resultpresensiDosen]);

  // Buat union dari minggu (weekKey) dari seluruh course
  const weeks = useMemo(() => {
    const weekSet = new Set();
    courses.forEach(course => {
      course.sessions.forEach(ws => {
        weekSet.add(ws.weekKey);
      });
    });
    return Array.from(weekSet).sort((a, b) => moment(a).diff(moment(b)));
  }, [courses]);

  return (
    <>

      <div tabIndex={0} className="collapse collapse-open collapse-arrow bg-white shadow-md sm:rounded-lg dark:bg-gray-800 p-4">

        <input type="checkbox" />

        <div className="collapse-title text-xl font-medium">Tabel Presensi Dosen</div>

        <div className="collapse-content">

          <h1 className="text-center font-semibold text-lg">{namaProdi}</h1>
          <h1 className="text-center font-semibold text-lg mb-4">{namaSemester}</h1>
          <table className="table-auto w-full border-collapse">
            <thead className="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th className="text-xs font-medium text-gray-500 uppercase">No</th>
                <th className="text-xs font-medium text-gray-500 uppercase">Nama Kelas</th>
                <th className="text-xs font-medium text-gray-500 uppercase">Nama Dosen</th>
                {weeks.map((week, idx) => (
                  <th key={idx} className="text-xs text-gray-500 uppercase text-center">
                    {`${moment(week).format("DD MMM YYYY")}`}
                  </th>
                ))}
                <th className="text-xs font-medium text-gray-500 uppercase text-center">
                  Total Kehadiran
                </th>
              </tr>
            </thead>
            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              {courses.map((course, courseIndex) => (
                course.dosens.map((dosen, dosenIndex) => {
                  const totalSessions = course.sessions.length;
                  const totalAttendance = weeks.reduce((acc, week) => acc + (dosen.weekCounts[week] || 0), 0);


                  return (


                    <tr key={`${course.course_id}-${dosenIndex}`}>
                      {dosenIndex === 0 && (
                        <>
                          <td rowSpan={course.dosens.length} className="text-xs text-gray-900" valign='top'>
                            {courseIndex + 1 + (page - 1) * perPage}
                          </td>
                          <td rowSpan={course.dosens.length} className="text-xs text-primary" valign='top' >
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
                      <td className="text-xs text-gray-900">{dosen.nama_dosen}</td>
                      {weeks.map((week, idx) => (
                        <td key={idx} className="text-xs text-center text-gray-900">
                          {dosen.weekCounts[week] || ''}
                        </td>
                      ))}
                      <td className="text-xs text-center text-gray-900">{totalAttendance}</td>

                    </tr>
                  );
                })

              ))}
            </tbody>
          </table>
          <div className="pagination mt-4">
            <Pagination links={resultpresensiDosen?.links} queryParams={queryParams} />
          </div>
        </div>
      </div>
    </>
  );





};

export default TablePresensi;
