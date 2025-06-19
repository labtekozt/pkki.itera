import React, { useState, useRef, useEffect } from 'react';
import { RefreshCw, Maximize2, Minimize2, BarChart3 } from 'lucide-react';
import BannerCustom from "../components/HeroCustom";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

const InfografisPage = () => {
  const [isFullscreen, setIsFullscreen] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const iframeRef = useRef(null);
  const containerRef = useRef(null);

  // URL Embed
  const dashboardUrl = 'https://lookerstudio.google.com/embed/reporting/0c04c8d5-d0bc-4cb4-ad59-96e7658516f7/page/kIV1C';

  const handleRefresh = () => {
    if (iframeRef.current) {
      setIsLoading(true);
      iframeRef.current.src = dashboardUrl;
    }
  };

  const toggleFullscreen = () => {
    setIsFullscreen(!isFullscreen);
  };

  const handleIframeLoad = () => {
    setIsLoading(false);
  };

  useEffect(() => {
    const handleEscKey = (event) => {
      if (event.key === 'Escape' && isFullscreen) {
        setIsFullscreen(false);
      }
    };

    document.addEventListener('keydown', handleEscKey);
    return () => document.removeEventListener('keydown', handleEscKey);
  }, [isFullscreen]);

  return (
    <>
    <Navbar />
    <BannerCustom name="Dashboard" />
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-red-50 p-4">
      <div className="max-w-full mx-auto">
        {/* Header */}
        <div className="mb-6 text-center">
          <div className="flex items-center justify-center mb-3">
            <BarChart3 className="w-8 h-8 text-red-600 mr-3" />
            <h1 className="text-2xl font-bold text-gray-800">Dashboard Analytics</h1>
          </div>
        </div>

        {/* Control Panel */}
        <div className="flex justify-center mb-4">
          <div className="bg-white rounded-xl shadow-md p-3 flex items-center gap-3">
            <button
              onClick={handleRefresh}
              disabled={isLoading}
              className="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 text-sm"
              title="Refresh Dashboard"
            >
              <RefreshCw className={`w-4 h-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
              {isLoading ? 'Loading...' : 'Refresh'}
            </button>
            
            <button
              onClick={toggleFullscreen}
              className="flex items-center px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-700 transition-all duration-200 text-sm"
              title={isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'}
            >
              {isFullscreen ? <Minimize2 className="w-4 h-4 mr-2" /> : <Maximize2 className="w-4 h-4 mr-2" />}
              {isFullscreen ? 'Exit' : 'Fullscreen'}
            </button>
          </div>
        </div>

        {/* Dashboard Container */}
        <div 
          ref={containerRef}
          className={`bg-white rounded-2xl shadow-2xl overflow-hidden transition-all duration-300 ${
            isFullscreen 
              ? 'fixed inset-2 z-50' 
              : 'relative mx-auto'
          }`}
        >
          {/* Dashboard Header Bar */}
          <div className="h-12 bg-gradient-to-r from-red-600 to-indigo-600 flex items-center justify-between px-6">
            <div className="flex items-center">
              <div className="flex space-x-2">
                <div className="w-2.5 h-2.5 bg-red-400 rounded-full"></div>
                <div className="w-2.5 h-2.5 bg-yellow-400 rounded-full"></div>
                <div className="w-2.5 h-2.5 bg-green-400 rounded-full"></div>
              </div>
              <span className="ml-4 text-white font-medium text-sm">Analytics Dashboard</span>
            </div>
            
            {isFullscreen && (
              <button
                onClick={toggleFullscreen}
                className="text-white hover:text-gray-200 transition-colors p-1"
                title="Exit Fullscreen"
              >
                <Minimize2 className="w-4 h-4" />
              </button>
            )}
          </div>

          {/* Dashboard Content */}
          <div className="relative">
            {/* Loading Overlay */}
            {isLoading && (
              <div className="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-20">
                <div className="flex flex-col items-center space-y-4">
                  <div className="relative">
                    <div className="w-12 h-12 border-4 border-red-200 rounded-full"></div>
                    <div className="w-12 h-12 border-4 border-red-600 border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                  </div>
                  <div className="text-center">
                    <p className="text-gray-600 font-medium">Memuat Dashboard...</p>
                    <p className="text-gray-500 text-sm">Sedang mengambil data terbaru</p>
                  </div>
                </div>
              </div>
            )}
            
            {/* Dashboard Iframe */}
            <iframe
              ref={iframeRef}
              src={dashboardUrl}
              className={`w-full border-0 transition-opacity duration-300 ${
                isFullscreen ? 'h-[calc(100vh-8rem)]' : 'h-[80vh] min-h-[600px]'
              } ${isLoading ? 'opacity-0' : 'opacity-100'}`}
              allowFullScreen
              onLoad={handleIframeLoad}
              title="Looker Studio Dashboard"
              frameBorder="0"
              style={{ 
                minHeight: isFullscreen ? 'calc(100vh - 8rem)' : '600px',
                background: '#f8fafc'
              }}
            />
          </div>
        </div>
      </div>
    </div>
    <Footer/>
    </>
  );
};

export default InfografisPage;