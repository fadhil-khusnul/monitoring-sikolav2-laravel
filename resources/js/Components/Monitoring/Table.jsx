import React from 'react';
import {
  Table as MuiTable,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Typography,
} from '@mui/material';
import Pagination from './Pagination';

const Table = ({ courses, queryParams = null }) => {
  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'));
  const namaSemester = savedParams?.selectedSemester.label ?? '';
  const namaProdi = savedParams?.selectedProgram.label ?? '';

  const page = courses?.current_page;
  const perPage = courses?.per_page;

  return (
    <TableContainer component={Paper}>
      <div className="p-8 text-gray-900 dark:text-gray-100">
        <Typography variant="h6" align="center" gutterBottom>
          {namaProdi}
        </Typography>
        <Typography variant="h6" align="center" gutterBottom>
          {namaSemester}
        </Typography>

        <MuiTable size="small">
          <TableHead>
            <TableRow>
              <TableCell>No</TableCell>
              <TableCell>Nama Kelas</TableCell>
              <TableCell>Dosen</TableCell>
              <TableCell>Alur Terisi</TableCell>
              <TableCell>Total Alur</TableCell>
              <TableCell>RPS</TableCell>
              <TableCell>Tugas</TableCell>
              <TableCell>Doc</TableCell>
              <TableCell>Survey</TableCell>
              <TableCell>Quiz</TableCell>
              <TableCell>Forum</TableCell>
              <TableCell>Reports</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {courses?.data?.map((course, index) => (
              <TableRow key={course.id}>
                <TableCell>{index + 1 + (page - 1) * perPage}</TableCell>
                <TableCell className='text-primary'>
                  <a
                    href={`https://sikola-v2.unhas.ac.id/course/view.php?id=${course.id}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className='text-primary hover:text-primary-700'
                  >
                    {course.fullname}
                  </a>
                </TableCell>
                <TableCell>
                  {course.dosens?.split('\\n').map((dosen, index) => (
                    <div key={index}>{dosen}</div>
                  ))}
                </TableCell>
                <TableCell>{course.totalBanyakTerisi}</TableCell>
                <TableCell>{course.totalBanyakAlur}</TableCell>
                <TableCell>{course.totalRPS}</TableCell>
                <TableCell>{course.totalTugas}</TableCell>
                <TableCell>{course.totalDocs}</TableCell>
                <TableCell>{course.totalSurvey}</TableCell>
                <TableCell>{course.totalQuiz}</TableCell>
                <TableCell>{course.totalForum}</TableCell>
                <TableCell>
                  <a
                    className="text-primary hover:text-primary-700"
                    href={`https://sikola-v2.unhas.ac.id/report/outline/index.php?id=${course.id}`}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    Activity Report
                  </a>
                  <br />
                  <a
                    className="text-primary hover:text-primary-700"
                    href={`https://sikola-v2.unhas.ac.id/report/progress/index.php?course=${course.id}`}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    Activity Completion
                  </a>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </MuiTable>

        <div className="pagination">
          <Pagination links={courses?.links} queryParams={queryParams} />
        </div>
      </div>
    </TableContainer>
  );
};

export default Table;
