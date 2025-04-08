import React from 'react';
import { Head } from '@inertiajs/inertia-react';

export default function Welcome({ auth }) {
  return (
    <>
      <Head>
        <title>Welcome - PKKI ITERA</title>
        <meta name="description" content="Welcome to PKKI ITERA Application" />
      </Head>
      
      <div className="relative min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
        {/* Header */}
        <header className="py-6 px-8">
          <div className="container mx-auto flex justify-between items-center">
            <div className="flex items-center">
              <img src="/images/logo.png" alt="PKKI ITERA Logo" className="h-10 w-auto" />
              <span className="ml-3 text-xl font-semibold">PKKI ITERA</span>
            </div>
            <div>
              {auth?.user ? (
                <a 
                  href="/admin" 
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                >
                  Dashboard
                </a>
              ) : (
                <a 
                  href="/admin/login" 
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                >
                  Login
                </a>
              )}
            </div>
          </div>
        </header>

        {/* Hero Section */}
        <main className="container mx-auto px-6 py-12 md:py-24">
          <div className="text-center max-w-3xl mx-auto">
            <h1 className="text-4xl md:text-5xl font-bold mb-6 text-gray-900 dark:text-white">
              Welcome to PKKI ITERA
            </h1>
            <p className="text-xl mb-10 text-gray-600 dark:text-gray-300">
              A modern web application built with Laravel, Inertia.js, React and Filament
            </p>
          </div>

          {/* Feature Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16 max-w-6xl mx-auto">
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-transform hover:scale-105">
              <div className="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h2 className="text-xl font-semibold mb-2 text-gray-900 dark:text-white">
                High Performance
              </h2>
              <p className="text-gray-600 dark:text-gray-300">
                Enjoy the speed and responsiveness of a single-page application with the SEO benefits of server-side rendering.
              </p>
            </div>
            
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-transform hover:scale-105">
              <div className="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
              </div>
              <h2 className="text-xl font-semibold mb-2 text-gray-900 dark:text-white">
                Filament Admin
              </h2>
              <p className="text-gray-600 dark:text-gray-300">
                Powerful admin interface with Filament, making content management intuitive and efficient.
              </p>
            </div>
            
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-transform hover:scale-105">
              <div className="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <h2 className="text-xl font-semibold mb-2 text-gray-900 dark:text-white">
                Modern Tech Stack
              </h2>
              <p className="text-gray-600 dark:text-gray-300">
                Built with Laravel, React - a robust foundation for your web applications.
              </p>
            </div>
          </div>
          
          <div className="text-center mt-16">
            <a 
              href="/admin" 
              className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition-all hover:shadow-lg"
            >
              Get Started
            </a>
          </div>
        </main>

        {/* Footer */}
        <footer className="py-8 mt-12 border-t border-gray-200 dark:border-gray-700">
          <div className="container mx-auto px-6 text-center text-gray-500 dark:text-gray-400">
            <p>© {new Date().getFullYear()} PKKI ITERA. All rights reserved.</p>
          </div>
        </footer>
      </div>
    </>
  );
}
