import Skeleton from '@mui/material/Skeleton';

const TableSkeleton = () => (
  <div className="bg-white shadow-md sm:rounded-lg dark:bg-gray-800 p-4">
    <div className="animate-pulse">
      {/* Header */}
      <div className="mb-8 space-y-2">
        <Skeleton variant="text" width="40%" height={40} className="mx-auto" />
        <Skeleton variant="text" width="35%" height={40} className="mx-auto" />
      </div>

      {/* Tabel Skeleton */}
      <table className="w-full border-collapse">
        <thead>
          <tr>
            {[...Array(4)].map((_, i) => (
              <th key={i} className="px-4 py-2">
                <Skeleton variant="text" width="100%" height={30} />
              </th>
            ))}
            {[...Array(4)].map((_, i) => ( // Sesuaikan jumlah kolom minggu
              <th key={i} className="px-4 py-2">
                <Skeleton variant="text" width="100%" height={30} />
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {[...Array(3)].map((_, i) => (
            <tr key={i}>
              {[...Array(4)].map((_, j) => (
                <td key={j} className="px-4 py-2">
                  <Skeleton variant="text" width="100%" height={25} />
                </td>
              ))}
              {[...Array(4)].map((_, j) => (
                <td key={j} className="px-4 py-2 text-center">
                  <Skeleton variant="text" width="60%" height={25} className="mx-auto" />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  </div>
);

export default TableSkeleton;
