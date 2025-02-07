export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-primary-600 dark:focus:ring-offset-gray-800 ' +
                className
            }
        />
    );
}
