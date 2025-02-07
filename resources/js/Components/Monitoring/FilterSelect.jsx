import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import { router } from '@inertiajs/react';

const FilterSelect = ({ semesterOptions }) => {
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
    setIsLoading(true)
    setIsFilterButtonDisabled(true)
    const filterParams = {
      selectedSemester,
      selectedProgram,
      selectedCourse,
    };
    sessionStorage.setItem('filterParams', JSON.stringify(filterParams));



    const queryParams = {};
    if (selectedSemester?.ta_semester) queryParams.ta_semester = selectedSemester.ta_semester;
    if (selectedSemester?.value) queryParams.id_semester = selectedSemester.value;
    if (selectedProgram?.value) queryParams.id_prodi = selectedProgram.value;
    if (selectedProgram?.kode_dikti) queryParams.kode_dikti = selectedProgram.kode_dikti;
    if (selectedCourse?.kode_matkul) queryParams.kode_matkul = selectedCourse.kode_matkul;


    const courseData = await fetchOptions('/getCourses', queryParams);
    if (courseData) {
      router.get(route('dashboard'), queryParams, { preserveState: true });
      setIsLoading(false)
      setIsFilterButtonDisabled(false)

      console.log(courseData);

    }








  };

  useEffect(() => {
    setIsFilterButtonDisabled(!selectedSemester || !selectedProgram);
  }, [selectedSemester, selectedProgram, selectedCourse]);

  return (
    <div className="flex flex-wrap mb-4 remove-input-txt-border">
      {/* Semester Select */}
      <div className="w-full sm:w-1/3 md:w-1/4 px-2 mb-4">
        <label className="block text-sm font-medium">Semester</label>
        <Select
          options={semesterOptions}
          value={selectedSemester}
          onChange={handleSemesterChange}
          isDisabled={isLoading}

          placeholder="Search Semester..."
        />
      </div>

      {/* Program Studi Select */}
      <div className="w-full sm:w-1/3 md:w-1/4 px-2 mb-4">
        <label className="block text-sm font-medium">Program Studi</label>
        <Select
          options={prodiOptions}
          value={selectedProgram}
          onChange={handleProgramChange}
          isDisabled={!selectedSemester || isLoading}
          placeholder="Search Program Studi..."
        />
      </div>

      {/* Matakuliah Select */}
      <div className="w-full sm:w-1/3 md:w-1/4 px-2 mb-4">
        <label className="block text-sm font-medium">Matakuliah</label>
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
  );
};

export default FilterSelect;
