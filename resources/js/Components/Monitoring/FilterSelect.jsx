
import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import Select from 'react-select';
import InputLabel from '../InputLabel';

const FilterSelect = ({ semesterOptions, filter }) => {
  const [selectedSemester, setSelectedSemester] = useState(null);
  const [selectedProgram, setSelectedProgram] = useState(null);
  const [selectedCourse, setSelectedCourse] = useState(null);
  const [prodiOptions, setProdiOptions] = useState([]);
  const [courseOptions, setCourseOptions] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isFilterButtonDisabled, setIsFilterButtonDisabled] = useState(true);
  const [isInitialLoad, setIsInitialLoad] = useState(true);


  useEffect(() => {
    // Load saved filters from session storage on component mount
    const savedFilters = JSON.parse(sessionStorage.getItem('filterParams'));
    if (savedFilters) {
      setSelectedSemester(savedFilters.selectedSemester || null);
      setSelectedProgram(savedFilters.selectedProgram || null);
      setSelectedCourse(savedFilters.selectedCourse || null);
    }

    setIsInitialLoad(false);


  }, []);


  useEffect(() => {
    if (selectedSemester && !isInitialLoad) {
      handleSemesterChange(selectedSemester, false);
    }
  }, [selectedSemester]);

  useEffect(() => {
    if (selectedProgram && !isInitialLoad) {
      handleProgramChange(selectedProgram, false);
    }
  }, [selectedProgram]);
  useEffect(() => {
    if (selectedCourse && !isInitialLoad) {
      handleCourseChange(selectedCourse, false);
    }
  }, [selectedCourse]);

  const resetDependentSelects = (level) => {
    if (level === 'semester') {
      setSelectedProgram(null);
      setSelectedCourse(null);
      setProdiOptions([]);
      setCourseOptions([]);
    } else if (level === 'program') {
      setSelectedCourse(null);
      setCourseOptions([]);
    }
  };

  const fetchOptions = async (endpoint, params) => {
    try {
      const response = await fetch(`${endpoint}?${new URLSearchParams(params)}`);
      if (response.ok) {
        return await response.json();
      }
      throw new Error('Failed to fetch data');
    } catch (error) {
      console.error(error);
      return [];
    }
  };

  const handleSemesterChange = async (option, isManualChange = true) => {
    setSelectedSemester(option);

    if (isManualChange) {
      resetDependentSelects('semester');
    }



    if (option) {
      setIsLoading(true);
      const prodiData = await fetchOptions('/getProdi', { id_semester: option.value });
      setProdiOptions(prodiData);
      setIsLoading(false);

      if (!isManualChange && selectedProgram) {
        handleProgramChange(selectedProgram, false);
      }
    }
  };

  const handleProgramChange = async (option, isManualChange = true) => {
    setSelectedProgram(option);
    if (isManualChange) {
      resetDependentSelects('program');
    }

    if (option && selectedSemester) {
      setIsLoading(true);
      const courseData = await fetchOptions('/getMatkul', {
        ta_semester: selectedSemester.ta_semester,
        id_prodi: option.value,
        kode_dikti: option.kode_dikti,
      });
      setCourseOptions(courseData);
      setIsLoading(false)

      if (!isManualChange && selectedCourse) {
        handleCourseChange(selectedCourse, false)
      }





    }
  };

  const handleCourseChange = async (option, isManualChange = true) => {
    setSelectedCourse(option);
  };



  const handleFilterSubmit = async () => {
    const params = new URLSearchParams(window.location.search);

    console.log(window.location.search);



    setIsLoading(true)
    setIsFilterButtonDisabled(true)
    const filterParams = {
      selectedSemester,
      selectedProgram,
      selectedCourse,
    };
    sessionStorage.setItem('filterParams', JSON.stringify(filterParams));






    console.log(filter);




    const queryParams = {};
    if (selectedSemester?.ta_semester) queryParams.ta_semester = selectedSemester.ta_semester;
    if (selectedSemester?.value) queryParams.id_semester = selectedSemester.value;
    if (selectedProgram?.value) queryParams.id_prodi = selectedProgram.value;
    if (selectedProgram?.kode_dikti) queryParams.kode_dikti = selectedProgram.kode_dikti;
    if (selectedCourse?.kode_matkul) queryParams.kode_matkul = selectedCourse.kode_matkul;

    if (filter) queryParams.filter = filter;





    if (selectedSemester &&selectedProgram) {
      const courseData = await fetchOptions('/getCourses', queryParams);

      params.set('page', 1);

      window.history.replaceState(null, '', '?' + params.toString());



      // router.get(route('dashboard'), queryParams, { preserveState: true });

      router.reload()
      setIsLoading(false)
      setIsFilterButtonDisabled(false)


    }









  };

  useEffect(() => {

    setIsFilterButtonDisabled(!selectedSemester || !selectedProgram);
    // setIsFilterButtonDisabled(!selectedSemester || !selectedProgram);
  }, [selectedSemester, selectedProgram, selectedCourse]);

  return (
    <div className="bg-white shadow-md sm:rounded-lg dark:bg-gray-800">
      <div className="p-8 text-gray-900 dark:text-gray-100">
        <div className="flex flex-wrap mb-4 remove-input-txt-border">
          <div className="select-container w-full sm:w-1/3 md:w-1/4 px-2 mb-4">
            {/* Semester Select */}
            <InputLabel htmlFor="Semester" value="Semester" />

            <Select
              classNamePrefix="react-select"

              options={semesterOptions}
              value={selectedSemester}
              onChange={handleSemesterChange}
              isDisabled={isLoading}

              placeholder="Search Semester..."
            />
          </div>

          <div className="w-full sm:w-1/3 md:w-1/4 px-2 mb-4 z-20">
            {/* Program Studi Select */}
            <InputLabel htmlFor="Program Studi" value="Program Studi" />

            <Select
              options={prodiOptions}
              value={selectedProgram}
              onChange={handleProgramChange}
              isDisabled={!selectedSemester || isLoading}
              placeholder="Search Program Studi..."
            />
          </div>

          <div className="w-full sm:w-1/3 md:w-1/4 px-2 mb-4">
            {/* Matakuliah Select */}
            <InputLabel htmlFor="Matakuliah" value="Matakuliah" />

            <Select
              options={courseOptions}
              value={selectedCourse}
              onChange={handleCourseChange}
              isDisabled={!selectedProgram || isLoading}
              placeholder="Search Matakuliah..."
            />
          </div>

          {/* Filter Button */}
          <div className="w-full sm:w-1/3 md:w-1/4 px-2 mt-6">
            <button
              onClick={handleFilterSubmit}
              disabled={isFilterButtonDisabled || isLoading}
              className="btn btn-sm btn-primary"
            >
              {isLoading ? 'Loading...' : 'Filter'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FilterSelect;
