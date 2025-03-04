import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import FilterSelect from '@/Components/Monitoring/FilterSelect';
import TableLogMahasiswa from '@/Components/Monitoring/TableLogMahasiswa';

export default function LogMahasiswa({ title, semesterOptions, resultLogMahasiswa }) {




  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {title}
        </h2>
      }
    >
      <Head title={title}/>
      <div className="">
        <div className="mx-auto sm:px-6 lg:py-4">
          <div className="shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div className=" text-gray-900 dark:text-gray-100">


              <FilterSelect
                semesterOptions={semesterOptions}
                filter='logmahasiswa'

              />


            </div>
          </div>
        </div>
      </div>

      <div className="py-12">
        <div className="mx-auto sm:px-6 lg:px-4">
          <div className="grid gap-4 mx-2 lg:grid-cols-1">
            <div className="w-full px-2 mb-4">

            </div>
            <div className="w-full px-2 mb-4">
            {resultLogMahasiswa?.data?.length > 0 ? <TableLogMahasiswa resultLogMahasiswa={resultLogMahasiswa} /> : null}


            </div>

          </div>
        </div>
      </div>



    </AuthenticatedLayout>
  );
}
