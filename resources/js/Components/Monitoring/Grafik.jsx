import React from 'react';
import { BarChart, PieChart } from '@mui/x-charts';
import { ResponsiveContainer } from 'recharts';

const Grafik = ({ totals, loading }) => {
  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'));
  const namaSemester = savedParams?.selectedSemester.label ?? '';
  const namaProdi = savedParams?.selectedProgram.label ?? '';

  // Data untuk chart
  const barData = [
    { label: 'Alur Pembelajaran', value: totals?.totalBanyakTerisi || 0 },
    { label: 'RPS', value: totals?.totalRps || 0 },
    { label: 'Tugas', value: totals?.totalTugas || 0 },
    { label: 'Doc', value: totals?.totalDoc || 0 },
    { label: 'Survey', value: totals?.totalSurvey || 0 },
    { label: 'Quiz', value: totals?.totalQuiz || 0 },
    { label: 'Forum', value: totals?.totalForum || 0 },
  ];

  const pieData = barData.map((item) => ({
    id: item.label,
    value: item.value,
    label: item.label,
  }));





  return (
    <div className="overflow-hidden bg-white shadow-md sm:rounded-lg dark:bg-gray-800">
      <div className="p-8 text-gray-900 dark:text-gray-100">
        <p className="text-center font-semibold text-lg mb-4">GRAFIK STATISTIK</p>
        <div className="mt-4 text-center">
          <h3 className="font-semibold">{namaProdi} {namaSemester}</h3>
        </div>
        <div className="flex flex-wrap gap-4 justify-center ">
          <div className="w-full md:w-1/3">
            <ResponsiveContainer width="100%" height={300}>
              <PieChart
                series={[
                  {
                    data: pieData,
                    arcLabel: (item) => `${item.value}`,
                    highlightScope: { faded: 'global', highlighted: 'item' },
                    faded: { innerRadius: 30, additionalRadius: -30 },
                  },
                ]}
              />
            </ResponsiveContainer>
          </div>

          <div className="w-full md:w-1/2">
            <ResponsiveContainer width="100%" height={300}>
              <BarChart
                xAxis={[
                  {
                    scaleType: 'band',
                    data: barData.map((item) => item.label),
                    label: 'Kategori',
                  }
                ]}
                series={[
                  {
                    data: barData.map((item) => item.value),
                    color: '#1976d2',
                  }
                ]}
              />
            </ResponsiveContainer>
          </div>
        </div>


      </div>
    </div>
  );
};

export default Grafik;
