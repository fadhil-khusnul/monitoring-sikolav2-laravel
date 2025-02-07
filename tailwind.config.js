import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.jsx',
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },

      // colors: {

      //   primary: {
      //     DEFAULT: '#0F6CC0',
      //     400: '#1A73E8',
      //     500: '#0F6CC0',
      //     600: '#0D5A9E',
      //     700: '#0B4A7D',
      //   },
      //   secondary: {
      //     DEFAULT: '#51666C',
      //     400: '#6B7D82',
      //     500: '#51666C',
      //     600: '#41555A',
      //     700: '#334448',
      //   },
      //   info: '#17a2b8',
      //   warning: '#ffc107',
      //   danger: '#dc3545',
      //   'text-light': '#f8f9fa',
      //   'text-dark': '#343a40',
      //   hover: {
      //     primary: '#0d5a9e',
      //     secondary: '#41555a',
      //     info: '#138496',
      //     warning: '#e0a800',
      //     danger: '#c82333',
      //   },
      //   button: {
      //     primary: '#0F6CC0',
      //     secondary: '#51666C',
      //     info: '#17a2b8',
      //     warning: '#ffc107',
      //     danger: '#dc3545',
      //   },
      // },
    },
  },

  daisyui: {
    themes: [
      {
        light: {
          "primary": "#0F6CC0",
          "secondary": "#51666C",
          "accent": "#FFFFFF",
          "neutral": "#0F6CC0",
          "base-100": "#FFFFFF",
          "info": "#17a2b8",
          "success": "#41ba5b",
          "warning": "#ffc107",
          "error": "#dc3545",
        },
      },
      {
        dark: {
          "primary": "#1A73E8",
          "secondary": "#FFFFFF",
          "accent": "#FFFFFF",
          "neutral": "#1A73E8",
          "base-100": "#1E293B",
          "info": "#138496",
          "success": "#34D399",
          "warning": "#FBBF24",
          "error": "#F87171",
        },
      },
    ],
  },

  plugins: [forms, require('daisyui')],
};
