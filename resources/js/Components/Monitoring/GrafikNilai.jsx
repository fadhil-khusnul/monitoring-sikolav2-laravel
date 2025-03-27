import React from 'react';
import Chart from "react-apexcharts";

const GrafikNilai = ({ totalSinkron, totalTidakSinkron }) => {
  console.log(totalSinkron);

  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'))
  const namaSemester = savedParams?.selectedSemester.label ?? ''
  const namaProdi = savedParams?.selectedProgram.label ?? ''



  const colors = ["#00e396", "#ff4560", "#775dd0", "#ffe200", "#798385"];


  const pieOptions = {
    chart: {
      height: 300,
      type: "pie",
      zoom: { enabled: true },
      toolbar: { show: true }
    },
    title: {
      text: `${namaProdi} ${namaSemester}`,
      align: "center",
      offsetY: 10,
      style: {
        fontSize: "14px",
        fontWeight: "bold",
        fontFamily: "Figtree",
        color: "#444"
      },
    },
    labels: ["Total Sinkron", "Total Tidak Sinkron"],
    colors: colors,
    legend: {
      show: true,
      position: "bottom",
      horizontalAlign: "center",
      verticalAlign: "middle",
      floating: false,
      fontSize: "14px",
      offsetX: 0,
      offsetY: 7
    },
    responsive: [{
      breakpoint: 600,
      options: {
        chart: { height: 240 },
        legend: { show: false }
      }
    }]
  };

  const pieSeries = [
    totalSinkron,
    totalTidakSinkron

  ];



  return (
    <div className="overflow-hidden bg-white shadow-md sm:rounded-lg dark:bg-gray-800 w-full">
    <div className="p-8 text-gray-900 dark:text-gray-100">
      <p className="text-center font-semibold text-lg mb-4">GRAFIK STATISTIK</p>
      <div className="justify-center">
        <Chart
          options={pieOptions}
          series={pieSeries}
          type="pie"
          width={'100%'}

        />
      </div>
    </div>
  </div>
  );
};

export default GrafikNilai;
