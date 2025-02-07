import React, { useEffect, useState } from 'react';
import {
  useQuery,

} from '@tanstack/react-query'
import Pagination from './Pagination';
const Table = ({courses, queryParams = null}) => {

  const page = courses?.current_page
  const perPage = courses?.per_page
  return (
    <div className="overflow-x-auto bg-white shadow-md sm:rounded-lg dark:bg-gray-800">
      <div className="p-8 text-gray-900 dark:text-gray-100">
        <p>Tabel</p>
        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead className="bg-gray-50 dark:bg-gray-700">

            <tr className='divide-x divide-y-2 divide-gray-200'>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                No
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nama Kelas
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Dosen
              </th>

              <th scope="col" colSpan={2} className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Alur Pembelajaran
              </th>

              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                RPS
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tugas
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Doc
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Survey
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Quiz
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Forum
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Reports
              </th>
            </tr>
            <tr className='divide-y divide-gray-200'>
              <th scope='col' className='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider' colSpan={3}>

              </th>

              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Alur Terisi
              </th>
              <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Total Alur
              </th>
              <th colSpan={7}></th>
            </tr>
          </thead>
          <tbody className="bg-white dark:bg-gray-800 divide-y  divide-gray-200 dark:divide-gray-700">
            {courses?.data?.map((course, index) => (
              <tr key={course.id}>
                <td className="px-6 py-4 whitespace-wrap text-sm font-medium text-gray-900 dark:text-gray-200">
                  {index + 1 + (page - 1) * perPage}

                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm font-medium text-primary hover:text-primary-700 dark:text-gray-200">
                  <a href={`https://sikola-v2.unhas.ac.id/course/view.php?id=${course.id}`} target='_blank'>
                    {course.fullname}
                  </a>
                </td>
                <td className="px-6 py-4 whitespace-nowrap break-words text-sm text-gray-500 dark:text-gray-300">
                  {course.dosens?.split('\\n').map((dosen, index) => (
                    <div key={index}>{dosen}</div>
                  ))}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalBanyakTerisi}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalBanyakAlur}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalRPS}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalTugas}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalDocs}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalSurvey}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalQuiz}
                </td>
                <td className="px-6 py-4 whitespace-wrap text-sm text-gray-500 dark:text-gray-300">
                  {course.totalForum}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                  <a className='text-primary hover:text-primary-700' href={`https://sikola-v2.unhas.ac.id/report/outline/index.php?id=${course.id}`} target='_blank'> Activity Report</a>
                  <br />
                  <a className='text-primary hover:text-primary-700' href={`https://sikola-v2.unhas.ac.id/report/progress/index.php?course=${course.id}`} target='_blank'> Activity Completion</a>

                </td>
              </tr>
            ))}
          </tbody>
        </table>


        <div className="pagination">
          <Pagination links={courses?.links} queryParams={queryParams} />

        </div>
      </div>
    </div>
  );
};

export default Table;
