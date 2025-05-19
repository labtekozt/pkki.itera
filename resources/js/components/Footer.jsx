import React from 'react';
import Logo from "../../../public/images/logo.png";

const Footer = () => {
  return (
    <>
      <footer className="bg-[#b21f2d] text-white py-10 md:px-20">
        <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row gap-10 md:gap-[100px]">

          <div className="md:w-1/2 flex-col md:flex-row gap-4">
            <div className="flex-shrink-0 mb-4">
              <img src={Logo} alt="Logo" className='w-72' />
            </div>

            <div>
              <p className="text-sm text-justify">
                Pusat Kelola Karya Intelektual adalah unit kerja di bawah Lembaga Pengembangan Pembelajaran dan Penjaminan Mutu (LP3M) Institut Teknologi Sumatera (ITERA) yang berfungsi untuk mengelola dan mendayagunakan kekayaan intelektual seluruh sivitas akademika ITERA, sekaligus sebagai pusat informasi dan pelayanan Pengajuan HKI .
              </p>
            </div>
          </div>

          <div className="md:w-1/2 flex flex-col sm:flex-row gap-10 justify-between mt-10">
            <div className='md:ml-20'>
              <h4 className="text-lg font-semibold mb-2">Link</h4>
              <ul className="space-y-1 text-sm">
                <li><a href="#" className="hover:underline">Instagram PKKI ITERA</a></li>
                <li><a href="#" className="hover:underline">LPMPP ITERA</a></li>
                <li><a href="#" className="hover:underline">DJKI KEMKUMHAM</a></li>
              </ul>
            </div>

            <div>
              <h4 className="text-lg font-semibold mb-2">Contact Info</h4>
              <ul className="space-y-1 text-sm">
                <li>
                  <p href="#" className="hover:underline">Alamat</p>
                  <a href="#" className="hover:underline">Gedung Training Center</a>
                </li>
                <li>
                  <a>Email: hki@itera.ac.id</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </footer>

      <div className="bg-black/80 p-5 w-full">
        <p className='text-sm text-center text-white'>COPYRIGHT &copy; ITERA 2025</p>
      </div>
    </>
  );
};

export default Footer;
