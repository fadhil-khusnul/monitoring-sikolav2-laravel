import 'react-select';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FilterSelect from '@/Components/Monitoring/FilterSelect';
import { Head } from '@inertiajs/react';
import Table from '@/Components/Monitoring/Table';
import Grafik from '@/Components/Monitoring/Grafik';
import Backdrop from '@mui/material/Backdrop';
import CircularProgress from '@mui/material/CircularProgress';

export default function Dashboard({ semesterOptions, courseDetails, total_grafik, shouldRefresh, title }) {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (shouldRefresh) {
      setOpen(true);
      // Tunda reload agar spinner terlihat oleh user
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    }
  }, [shouldRefresh]);

  return (
    <>
      <Backdrop

        sx={(theme) => ({
          color: 'rgba(0, 0, 0, 0.9)',
          zIndex: theme.zIndex.drawer + 1,
          backgroundColor: '#fff',
          backdropFilter: 'blur(4px)'
        })}
        open={open}
      >
        <CircularProgress color="inherit" />
      </Backdrop>

      {!shouldRefresh && (
        <AuthenticatedLayout
          header={
            <h2 className="text-xl font-semibold leading-tight text-gray-800 bg-gray-100 dark:text-gray-200">
              {title}
            </h2>
          }
        >
          <Head title={title} />

          <div className="py-2">
            <div className="mx-auto sm:px-6 lg:px-4">
              <div className="grid gap-4 mx-2 lg:grid-cols-1">
                <div className="w-full px-2 mb-4">
                  <FilterSelect semesterOptions={semesterOptions} filter={'statistik'} />
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
      )}

    </>
  );
}
