import React, { useState, useEffect } from 'react';
import { Link } from "@inertiajs/inertia-react";
import Logo from "../../../public/images/logo.png"; 

const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false); // To handle mobile menu open/close
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
          <Link href={"/"}>
            <img src={Logo} alt="Logo" className='w-20 md:w-40' />
          </Link>
        </div>
        <div className="hidden md:flex space-x-4 font-manrope">
          <Link href={"/"} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>Home</Link>
          <Link href={"/kegiatan"} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>Kegiatan</Link>
          <Link href={"/infografis"} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>Infografis</Link>
          <Link href={"/news"} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>Berita</Link>
          <Link href={"/kontak"} className={`nav-link ${isSticky ? 'text-white' : 'text-white'}`}>Kontak</Link>
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

          <Link href={"/"} className="block nav-link py-2 px-4">Home</Link>
          <Link href={"/laporan"} className="block nav-link py-2 px-4">Laporan</Link>
          <Link href={"/kegiatan"} className="block nav-link py-2 px-4">Kegiatan</Link>
          <Link href={"/infografis"} className="block nav-link py-2 px-4">Infografis</Link>
          <Link href={"/berita"} className="block nav-link py-2 px-4">Berita</Link>
          <Link href={"/kontak"} className="block nav-link py-2 px-4">Kontak</Link>
        </div>
      )}
    </nav>
  );
};

export default Navbar;
