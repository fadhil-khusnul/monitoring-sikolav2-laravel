import { Skeleton } from '@mui/material';

const SkeletonGrafik = () => (
  <div className="overflow-hidden bg-white shadow-md sm:rounded-lg dark:bg-gray-800 p-8">
    <Skeleton variant="text" width="60%" height={40} className="mx-auto mb-4" />

    <div className="flex flex-wrap">
      <div className="w-full md:w-1/2">
        <Skeleton variant="rectangular" width="100%" height={380} />
      </div>

      <div className="w-full md:w-1/2">
        <Skeleton variant="rectangular" width="100%" height={380} />
      </div>
    </div>


  </div>
);


export default SkeletonGrafik;
