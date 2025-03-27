import React, { useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query'
import Pagination from './Pagination';
import moment from 'moment';

const TableLogDosen = ({ logs, queryParams = null }) => {
  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'))
  const namaSemester = savedParams?.selectedSemester.label ?? ''
  const namaProdi = savedParams?.selectedProgram.label ?? ''

  const page = logs?.current_page
  const perPage = logs?.per_page

  console.log(logs);


  const courses = useMemo(() => {
    if (!logs?.data) return [];
    return logs.data.map(course => {
      // Kelompokkan session berdasarkan minggu
      const sessionsByWeek = {};
      course.sessionDosen.forEach(session => {
        const sessDate = moment.unix(session.sessdate);
        const weekKey = sessDate.startOf('isoWeek').format("YYYY-MM-DD");

        if (!sessionsByWeek[weekKey]) {
          sessionsByWeek[weekKey] = {
            startDate: sessDate.clone().startOf('isoWeek'),
            endDate: sessDate.clone().endOf('isoWeek')
          };
        }
      });

      // Buat array sessions dengan rentang tanggal mingguan
      const transformedSessions = Object.keys(sessionsByWeek)
        .sort((a, b) => moment(a).diff(moment(b)))
        .map(weekKey => ({
          weekKey,
          startDate: sessionsByWeek[weekKey].startDate,
          endDate: sessionsByWeek[weekKey].endDate
        }));

      // Transformasi data dosen
      const dosens = course.logsDosen.map(dosen => {
        const weekCounts = {};
        transformedSessions.forEach(ws => {
          weekCounts[ws.weekKey] = 0;
        });

        // Hitung log per minggu
        dosen.logs.forEach(record => {
          const logDate = moment.unix(record.timecreated);
          transformedSessions.forEach(ws => {
            if (logDate.isBetween(ws.startDate, ws.endDate, null, '[]')) {
              weekCounts[ws.weekKey]++;
            }
          });
        });

        return {
          nama_dosen: dosen.nama_dosen,
          nip: dosen.nip,
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
  }, [logs]);

  // Kumpulkan semua minggu unik dari semua course
  const weeks = useMemo(() => {
    const weekMap = new Map();
    courses.forEach(course => {
      course.sessions.forEach(ws => {
        if (!weekMap.has(ws.weekKey)) {
          weekMap.set(ws.weekKey, {
            startDate: ws.startDate,
            endDate: ws.endDate
          });
        }
      });
    });
    return Array.from(weekMap.values()).sort((a, b) => a.startDate.diff(b.startDate));
  }, [courses]);

  return (
    <div tabIndex={0} className="collapse collapse-open bg-white shadow-md sm:rounded-lg dark:bg-gray-800 p-4">
      <input type="checkbox" />
      <div className="collapse-title text-xl font-medium">Tabel Log Dosen</div>

      <div className="collapse-content">
        <h1 className="text-center font-semibold text-lg">{namaProdi}</h1>
        <h1 className="text-center font-semibold text-lg mb-4">{namaSemester}</h1>

        <table className="table-auto w-full border-collapse">
          <thead className="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th className="text-xs font-medium text-gray-500 uppercase">No</th>
              <th className="text-xs font-medium text-gray-500 uppercase" width="20%">Nama Kelas</th>
              <th className="text-xs font-medium text-gray-500 uppercase">NIP</th>
              <th className="text-xs font-medium text-gray-500 uppercase">Nama Dosen</th>
              {weeks.map((week, idx) => (
                <th key={idx} className="text-xs text-gray-500 uppercase text-center">
                  {`${week.startDate.format("DD MMM")} - ${week.endDate.format("DD MMM")}`}
                </th>
              ))}
            </tr>
          </thead>

          <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            {courses.map((course, courseIndex) => (
              course.dosens.map((dosen, dosenIndex) => (
                <tr key={`${course.course_id}-${dosenIndex}`}>
                  {dosenIndex === 0 && (
                    <>
                      <td rowSpan={course.dosens.length} className="text-xs text-gray-900" valign='top'>
                        {courseIndex + 1 + (page - 1) * perPage}
                      </td>
                      <td rowSpan={course.dosens.length} className="text-xs text-primary" valign='top'>
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
                  <td className="text-xs text-gray-900" valign='top'>{dosen.nip.toUpperCase()}</td>
                  <td className="text-xs text-gray-900">{dosen.nama_dosen}</td>
                  {weeks.map((week, idx) => (
                    <td key={idx} className="text-xs text-center text-gray-900">
                      {dosen.weekCounts[week.startDate.format("YYYY-MM-DD")] || ''}
                    </td>
                  ))}
                </tr>
              ))
            ))}
          </tbody>
        </table>

        <div className="pagination mt-4">
          <Pagination links={logs?.links} queryParams={queryParams} />
        </div>
      </div>
    </div>
  );
};

export default TableLogDosen;
