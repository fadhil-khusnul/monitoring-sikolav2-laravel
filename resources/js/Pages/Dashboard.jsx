import 'react-select';
import { useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FilterSelect from '@/Components/Monitoring/FilterSelect';
import { Head, router, usePage } from '@inertiajs/react';
import Table from '@/Components/Monitoring/Table';
import Grafik from '@/Components/Monitoring/Grafik';

export default function Dashboard({ semesterOptions, courseDetails, total_grafik }) {


  // useEffect(() => {
  //   if (window.performance) {
  //     if (performance.navigation.type === 1) {
  //       router.reload({ only: ['semesterOptions', 'courseDetails', 'total_grafik'] });
  //     }
  //   }
  // }, []);
  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 bg-gray-100 dark:text-gray-200">
          Dashboard Statistik Matakuliah
        </h2>
      }
    >
      <Head title="Dashboard" />


      <div className="py-2">
        <div className="mx-auto sm:px-6 lg:px-4">
          <div className="grid gap-4 mx-2 lg:grid-cols-1">
            <div className='w-full px-2 mb-4'>

              <FilterSelect
                semesterOptions={semesterOptions}
                filter={'statistik'}

              />
            </div>
            <div className="w-full px-2 mb-4">

              {total_grafik ? <Grafik totals={total_grafik} /> : null}

            </div>
            <div className="w-full px-2 mb-4">
              {courseDetails.data.length > 0 ? <Table courses={courseDetails} /> : null}


            </div>

          </div>
        </div>
      </div>



    </AuthenticatedLayout>
  );
}
