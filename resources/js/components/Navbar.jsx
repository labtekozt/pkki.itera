import React, { useState, useEffect } from 'react';
// import { Link } from "@inertiajs/inertia-react";
import Logo from "../../../public/images/logo.png";

const handleHome = () => {
    window.location.href = "/";
};
const handlePanduan = () => {
    window.location.href = "/kegiatan";
};
const handleKontak = () => {
    window.location.href = "/kontak";
};
const handleInfografis = () => {
    window.location.href = "/infografis";
};

const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false); 
  const [isSticky, setIsSticky] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      if (window.scrollY > 50) {
        setIsSticky(true);
      } else {
        setIsSticky(false);
      }
    };

    window.addEventListener('scroll', handleScroll);

    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);


  return (
    <nav className={`navbar md:sticky ${isSticky ? 'navbar-fixed' : 'absolute'}`}>
      <div className="container mx-auto flex items-center justify-between p-4">
        <div className="flex items-center space-x-4">
          <button onClick={handleHome}>
            <img src={Logo} alt="Logo" className='w-20 md:w-40' />
          </button>
        </div>
        <div className="hidden md:flex space-x-4 font-manrope">
          <button onClick={handleHome} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`} prefetch>Home</button>
          <button onClick={handlePanduan} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`} prefetch>Panduan</button>
          <button onClick={handleInfografis} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`} prefetch>Infografis</button>
          {/* <Link href={"#"} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>Berita</Link> */}
          <button onClick={handleKontak} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`} prefetch>Kontak</button>
        </div>

        <div className="md:hidden">
          <button onClick={() => setIsOpen(!isOpen)} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
          </button>
        </div>
      </div>

      {isOpen && (
        <div className={`md:hidden p-2 font-manrope font-medium ${isSticky ? 'bg-[#B82132] bg-opacity-40 text-white' : 'bg-[#B82132] text-white'}`}>

          <button onClick={handleHome} className="block nav-link py-2 px-4" prefetch>Home</button>
          <button onClick={handlePanduan} className="block nav-link py-2 px-4" prefetch>Panduan</button>
          <button onClick={handleInfografis} className="block nav-link py-2 px-4" prefetch>Infografis</button>
          {/* <Link href={"#"} className="block nav-link py-2 px-4">Berita</Link> */}
          <button onClick={handleKontak} className="block nav-link py-2 px-4" prefetch>Kontak</button>
        </div>
      )}
    </nav>
  );
};

export default Navbar;
