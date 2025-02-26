import { Link } from "@inertiajs/react";

export default function Pagination({ links, queryParams }) {


  const params = new URLSearchParams(queryParams);


  params.delete('page');


  return (
    <nav className="text-center mt-4">
      {links?.map((link) => (

        <Link
          preserveScroll
          href={`${link.url}` || ""}
          key={link.label}
          className={
            "inline-block py-2 px-3 rounded-lg  text-xs " +
            (link.active ? "bg-gray-950 text-gray-300 " : "text-gray-500 ") +
            (!link.url
              ? "!text-gray-300 cursor-not-allowed "
              : "hover:bg-gray-950 hover:text-gray-300 ")
          }
          dangerouslySetInnerHTML={{ __html: link.label }}
        ></Link>
      ))}
    </nav>
  );
}
