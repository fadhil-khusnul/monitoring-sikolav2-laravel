import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import './login.css'
export default function Login({ status, canResetPassword }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    login: '',
    password: '',
    remember: false,
  });

  const submit = (e) => {
    e.preventDefault();

    post(route('login'), {
      onFinish: () => reset('password'),
    });
  };

  return (


    <div className='main flex min-h-screen flex-col items-center bg-gray-100 pt-4 sm:justify-center sm:pt-0 dark:bg-gray-900'>
      <Head title="Log in" />
      <section className="signup">
        <div className="container">
          <div className="signup-content">
            <div className="signup-form">
              <h2 className="">Log In</h2>
              <h4 className="form-title">Website Monitoring SIKOLA 2.0</h4>
              <form onSubmit={submit} className="register-form text-gray-900 dark:text-gray-100" id="register-form">
                <div className="form-group">


                  <TextInput
                    id="login"
                    type="text"
                    name="login"
                    value={data.login}
                    placeholder="Username/Email"
                    // className="mt-1 block w-full"
                    autoComplete="username"
                    isFocused={true}
                    onChange={(e) => setData('login', e.target.value)}
                  />
                  <InputError message={errors.login} className="mt-2" />


                </div>
                <div className="form-group">


                  <TextInput
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Password"
                    value={data.password}
                    // className="mt-1 block w-full"
                    autoComplete="current-password"
                    onChange={(e) => setData('password', e.target.value)}
                  />

                </div>

                <div className="mt-4 form-group">
                  <label className="flex items-center">
                    <Checkbox
                      name="remember"
                      checked={data.remember}
                      onChange={(e) =>
                        setData('remember', e.target.checked)
                      }
                    />
                    <span className="ms-2 text-sm text-gray-600 dark:text-gray-400">
                      Remember me
                    </span>
                  </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                  {canResetPassword && (
                    <Link
                      href={route('password.request')}
                      className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                      Forgot your password?
                    </Link>
                  )}

                  <PrimaryButton className="ms-4 " disabled={processing}>
                    Log in
                  </PrimaryButton>
                </div>


                {/* <div className="form-group form-button">
                  <input
                    type="submit"
                    name="signup"
                    id="signup"
                    className="form-submit"
                    defaultValue="Log In"
                  />
                </div> */}
              </form>
            </div>
            <div className="signup-image">
              <figure>
                <img src="/images/sigin.png" alt="sing up image" />
              </figure>
            </div>
          </div>
        </div>
      </section>


      {/* <GuestLayout>
        <Head title="Log in" />

        {status && (
          <div className="mb-4 text-sm font-medium text-green-600">
            {status}
          </div>
        )}

        <form onSubmit={submit} className='text-gray-900 dark:text-gray-100'>
          <div>
            <InputLabel htmlFor="email" value="Username/Email" />

            <TextInput
              id="login"
              type="text"
              name="login"
              value={data.login}
              className="mt-1 block w-full"
              autoComplete="username"
              isFocused={true}
              onChange={(e) => setData('login', e.target.value)}
            />

            <InputError message={errors.login} className="mt-2" />
          </div>

          <div className="mt-4">
            <InputLabel htmlFor="password" value="Password" />



            <InputError message={errors.password} className="mt-2" />
          </div>


        </form>
      </GuestLayout> */}
    </div>
  );
}
