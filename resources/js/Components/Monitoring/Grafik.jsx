import React from 'react';
import Chart from "react-apexcharts";

const Grafik = ({ totals }) => {
  console.log(totals);

  const savedParams = JSON.parse(sessionStorage.getItem('filterParams'))
  const namaSemester = savedParams?.selectedSemester.label ?? ''
  const namaProdi = savedParams?.selectedProgram.label ?? ''



  const colors = ["#008ffb", "#00e396", "#feb019", "#ff4560", "#775dd0", "#ffe200", "#798385"];
  const options = {
    chart: {
      height: 380,
      type: "bar",
      zoom: { enabled: true },
      toolbar: { show: true },


    },
    plotOptions: {
      bar: {
        dataLabels: { position: "top" },
        distributed: true
      }
    },
    dataLabels: {
      enabled: true,
      offsetY: -30,
      style: { fontSize: "12px", colors: ["#304758"] }
    },
    colors: colors,
    labels: ["Alur Pembelajaran (Terisi)", "RPS", "Tugas", "Doc", "Survey", "Quiz", "Forum"],
    xaxis: {
      axisBorder: { show: true },
      axisTicks: { show: true },
      crosshairs: {
        fill: {
          type: "gradient",
          gradient: {
            colorFrom: "#D8E3F0",
            colorTo: "#BED1E6",
            stops: [0, 100],
            opacityFrom: 0.4,
            opacityTo: 0.5
          }
        }
      },
      tooltip: { enabled: false, offsetY: -35 }
    },
    fill: {
      gradient: {
        enabled: false,
        shade: "light",
        type: "horizontal",
        shadeIntensity: 0.25,
        gradientToColors: undefined,
        inverseColors: true,
        opacityFrom: 1,
        opacityTo: 1,
        stops: [50, 0, 100, 100]
      }
    },
    yaxis: {
      axisBorder: { show: true },
      axisTicks: { show: true },
      labels: { show: true },
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
    grid: {
      row: { colors: ["transparent", "transparent"], opacity: 0.2 },
      borderColor: "#f1f3fa"
    },
    responsive: [{
      breakpoint: 600,
      options: {
        chart: {
          height: 240
        },
        plotOptions: {
          bar: {
            horizontal: true
          }
        },
        legend: {
          position: "bottom"
        }
      }
    }]
  };
  const series = [{
    name: "Total",
    data: [
      totals?.totalBanyakTerisi,
      totals?.totalRps,
      totals?.totalTugas,
      totals?.totalDoc,
      totals?.totalSurvey,
      totals?.totalQuiz,
      totals?.totalForum
    ]
  }];


  const pieOptions = {
    chart: {
      height: 380,
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
    labels: ["Alur Pembelajaran (Terisi)", "RPS", "Tugas", "Doc", "Survey", "Quiz", "Forum"],
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
    totals?.totalBanyakTerisi,
    totals?.totalRps,
    totals?.totalTugas,
    totals?.totalDoc,
    totals?.totalSurvey,
    totals?.totalQuiz,
    totals?.totalForum
  ];



  return (
    <div className="overflow-hidden bg-white shadow-md sm:rounded-lg dark:bg-gray-800">
      <div className="p-8 text-gray-900 dark:text-gray-100">
        <p className='text-center font-semibold text-lg mb-4'>GRAFIK STATISTIK</p>

        <div className='flex flex-wrap'>
          <div className="w-full md:w-1/2">
            <Chart
              options={pieOptions}
              series={pieSeries}
              type="pie"
              width="60%"
            />
          </div>

          <div className="w-full md:w-1/2">
            <Chart
              options={options}
              series={series}
              type="bar"
              width="700"
            />
          </div>
        </div>





      </div>
    </div>
  );
};

export default Grafik;
