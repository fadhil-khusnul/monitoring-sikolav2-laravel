import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import FilterSelect from '@/Components/Monitoring/FilterSelect';
import TablePresensi from '@/Components/Monitoring/TablePresensi';
import { Tab } from '@mui/material';
import TablePresensiMahasiswa from '@/Components/Monitoring/TablePresensiMahasiswa';

export default function Presensi({ semesterOptions, resultpresensiDosen, resultPresensiMahasiswa, title }) {

  console.log(resultPresensiMahasiswa);





  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {title}
        </h2>
      }
    >
      <Head title={title} />
      <div className="py-2">
        <div className="mx-auto sm:px-6 lg:px-4">
          <div className="grid gap-2 mx-2 lg:grid-cols-1">
            <div className='w-full px-2 mb-4'>

              <FilterSelect
                semesterOptions={semesterOptions}
                filter='presensi'

              />
            </div>

            <div className="w-full px-2 mb-4">
              {resultpresensiDosen?.data.length > 0 ? <TablePresensi resultpresensiDosen={resultpresensiDosen} /> : null}


            </div>
            <div className="w-full px-2 mb-4">
              {resultPresensiMahasiswa?.data.length > 0 ? <TablePresensiMahasiswa resultpresensiMahasiswa={resultPresensiMahasiswa} /> : null}


            </div>

          </div>
        </div>
      </div>



    </AuthenticatedLayout>
  );
}
