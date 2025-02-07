import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import Select from 'react-select';
import { useState, useEffect } from 'react';
import Table from '@/Components/Monitoring/Table';
import Grafik from '@/Components/Monitoring/Grafik';
import FilterSelect from '@/Components/Monitoring/FilterSelect';
import {
  QueryClient,
  QueryClientProvider,
} from '@tanstack/react-query'

export default function Dashboard({ semesterOptions, courseDetails, total_grafik }) {


  console.log(total_grafik);


  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Dashboard
        </h2>
      }
    >
      <Head title="Dashboard" />
      <div className="">
        <div className="mx-auto sm:px-6 lg:py-4">
          <div className="shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div className=" text-gray-900 dark:text-gray-100">


              <FilterSelect
                semesterOptions={semesterOptions}

              />


            </div>
          </div>
        </div>
      </div>

      <div className="py-12">
        <div className="mx-auto sm:px-6 lg:px-4">
          <div className="grid gap-4 mx-2 lg:grid-cols-1">
            <div className="w-full px-2 mb-4">
              <Grafik totals={total_grafik} />

            </div>
            <div className="w-full px-2 mb-4">

                <Table courses={courseDetails} />

            </div>

          </div>
        </div>
      </div>



    </AuthenticatedLayout>
  );
}
