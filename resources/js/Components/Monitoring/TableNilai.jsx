import React, { useState } from 'react';
import {
  Table as MuiTable,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Typography,
  Button,
  IconButton,
  Chip,
  TablePagination,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
} from '@mui/material';
import Pagination from './Pagination';
import { ArrowTopRightOnSquareIcon, UserIcon, UsersIcon } from '@heroicons/react/24/outline';
import { Link } from '@inertiajs/react';
import { PeopleAlt } from '@mui/icons-material';
import Modal from '../Modal';

const TableNilai = ({ courses, queryParams = null }) => {
  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'));
  const namaSemester = savedParams?.selectedSemester.label ?? '';
  const namaProdi = savedParams?.selectedProgram.label ?? '';

  const page = courses?.current_page;
  const perPage = courses?.per_page;

  const [showModal, setShowModal] = useState(false);
  const [selectedGradeItems, setSelectedGradeItems] = useState([]);
  const [selectedNilaiMahasiswa, setSelectedNilaiMahasiswa] = useState([]);
  const [namaKelas, setNamaKelas] = useState([]);

  const [pageModal, setPage] = useState(0); // State untuk halaman aktif
  const [rowsPerPage, setRowsPerPage] = useState(5); // State untuk jumlah item per halaman


  const handleShowModal = (gradeItems, nilaiMahasiswa, namaKelas) => {
    setSelectedGradeItems(gradeItems);
    setSelectedNilaiMahasiswa(nilaiMahasiswa);
    setNamaKelas(namaKelas)
    setShowModal(true);
  };

  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  const handleClose = () => {
    setShowModal(false);
  };



  return (

    <>
      <TableContainer component={Paper} className='w-full'>
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
                <TableCell width={'25%'}>Nama Kelas</TableCell>
                <TableCell>Dosen</TableCell>
                <TableCell>Nilai Mahasiswa</TableCell>
                <TableCell>Link Grade Sikola</TableCell>
                <TableCell className='nowrap'>Status</TableCell>
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


                  <TableCell>
                    <Button
                      variant="outlined"
                      size="small"
                      endIcon={<PeopleAlt />}
                      onClick={() => handleShowModal(course.gradeItems, course.nilaiMahasiswa,  course.fullname)}
                    >
                      Show
                    </Button>
                  </TableCell>

                  <TableCell>
                    <a
                      className="flex flex-wrap text-primary hover:text-primary-700 gap-2"
                      href={`https://sikola-v2.unhas.ac.id/grade/report/grader/index.php?id=${course.id}`}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Link  <ArrowTopRightOnSquareIcon width={15} />
                    </a>

                  </TableCell>
                  <TableCell>
                    {
                      course.status == 1 ? <span className='bg-green-500 text-white px-2 py-1 rounded'>Sinkron</span> : <span className='bg-red-500 text-white px-2 py-1 rounded'>Belum Sinkron</span>
                    }

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

      {showModal && (


        <Dialog
          maxWidth={'lg'}
          open={showModal}
          onClose={handleClose}
        >
          <DialogTitle>Tabel Penilaian Mahasiswa {namaKelas}</DialogTitle>
          <DialogContent>

            <MuiTable size="small" stickyHeader>
              <TableHead>
                <TableRow>
                  <TableCell>No</TableCell>
                  <TableCell>NIM</TableCell>
                  <TableCell>Nama Mahasiswa</TableCell>
                  {selectedGradeItems.map((item, index) => (
                    <TableCell key={index}>{item.itemtype == 'course' ? 'Nilai Akhir' : `${item.itemname}`}</TableCell>
                  ))}
                </TableRow>
              </TableHead>
              <TableBody>
                {selectedNilaiMahasiswa
                  .slice(pageModal * rowsPerPage, pageModal * rowsPerPage + rowsPerPage) // Pagination logic
                  .map((mahasiswa, index) => (
                    <TableRow key={mahasiswa.userid}>
                      <TableCell>{index + 1 + pageModal * rowsPerPage}</TableCell>
                      <TableCell>{mahasiswa.userfullname}</TableCell>
                      <TableCell>{mahasiswa.userfullname}</TableCell>
                      {selectedGradeItems.map((item, idx) => {
                        const nilai = mahasiswa.gradeitems.find(
                          (gradeItem) => gradeItem.id === item.id
                        );
                        return (
                          <TableCell key={idx}>
                            {nilai.itemtype == 'course' ? `${item.gradeformatted}`: `${item.graderaw}`}
                          </TableCell>
                        );
                      })}
                    </TableRow>
                  ))}
              </TableBody>
            </MuiTable>
            <TablePagination
              rowsPerPageOptions={[5, 10, 25]}
              component="div"
              count={selectedNilaiMahasiswa.length}
              rowsPerPage={rowsPerPage}
              page={pageModal}
              onPageChange={handleChangePage}
              onRowsPerPageChange={handleChangeRowsPerPage}
            />
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Close</Button>
          </DialogActions>
        </Dialog>

      )}

    </>


  );
};

export default TableNilai;
