import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import Select from 'react-select';
import { useState, useEffect } from 'react';
import FilterSelect from '@/Components/Monitoring/FilterSelect';
import TableNilai from '@/Components/Monitoring/TableNilai';
import GrafikNilai from '@/Components/Monitoring/GrafikNilai';
import TableSkeleton from '@/Components/Monitoring/TableSkeleton';
import SkeletonGrafik from '@/Components/Monitoring/SkeletonGrafik';

export default function Nilai({ semesterOptions, grades, totalSinkron, totalTidakSinkron, title }) {

  const [isLoading, setIsLoading] = useState(false);



  useEffect(() => {
    // Reset loading state ketika data berubah
    if (grades) {
      setIsLoading(false);
    }
  }, [grades]);

  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {title}
        </h2>
      }
    >
      <Head title={title} />
      <div className="">
        <div className="mx-auto sm:px-6 lg:py-4">
          <div className="shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div className=" text-gray-900 dark:text-gray-100">


              <FilterSelect
                semesterOptions={semesterOptions}
                filter={'nilai'}
                onFilterStart={() => setIsLoading(true)}


              />


            </div>
          </div>
        </div>
      </div>

      <div className="py-12">
        <div className="mx-auto sm:px-6 lg:px-4">
          <div className="grid gap-4 mx-2">
            <div className="flex flex-wrap lg:flex-nowrap gap-2">
              {/* Grafik */}
              <div className="w-full lg:w-1/3 px-2 mb-4">
                {isLoading ? <SkeletonGrafik /> : totalSinkron ? (
                  <GrafikNilai
                    totalSinkron={totalSinkron}
                    totalTidakSinkron={totalTidakSinkron}
                  />
                ) : null}
              </div>

              {/* Tabel */}
              <div className="w-full lg:w-2/3 px-2 mb-4">
                {isLoading ? <TableSkeleton /> : grades.data.length > 0 ? <TableNilai courses={grades} /> : null}
              </div>
            </div>
          </div>
        </div>
      </div>




    </AuthenticatedLayout>
  );
}
