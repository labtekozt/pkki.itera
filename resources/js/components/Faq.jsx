import React, { useState } from 'react';

const Faq = () => {
  const [open, setOpen] = useState(0); // First item open by default

  const toggleAccordion = (index) => {
    setOpen(open === index ? null : index); // If already open, close it
  };

  return (
    <section className="bg-white text-black py-16 mt-10 md:px-20">
      <div className="max-w-7xl mx-auto px-4 text-center">
        <h2 className="text-3xl font-bold mb-4">FAQ</h2>
        <p className="text-sm mb-8 text-gray-600">
          Dari sudut pandang yang berbeda, kita bisa melihat bahwa inovasi adalah kunci untuk mencapai keberhasilan.
        </p>

        {/* Accordion Section */}
        <div className="space-y-6">
          {/* Accordion Item 1 */}
          <div className="bg-gray p-6 rounded-lg cursor-pointer hover:shadow-lg transition-all duration-300" onClick={() => toggleAccordion(0)}>
            <div className="flex justify-between items-center">
              <h4 className="font-semibold text-lg text-black">Apa itu PKKI?</h4>
              <span className="text-xl">{open === 0 ? '-' : '+'}</span>
            </div>
            <div
              className={`
                overflow-hidden transition-all duration-500 ease-in-out 
                ${open === 0 ? 'max-h-96 opacity-100 scale-y-100' : 'max-h-0 opacity-0 scale-y-50'}
              `}
            >
              {(open === 0 || open === null) && (
                <p className="mt-2 text-sm text-justify text-gray-700">
                  Pusat Kreatif Karya Intelektual (PKKI) adalah lembaga yang mengelola data inovasi dan kekayaan intelektual melalui teknologi informasi di seluruh Indonesia.
                </p>
              )}
            </div>
          </div>

          {/* Accordion Item 2 */}
          <div className="bg-gray p-6 rounded-lg cursor-pointer hover:shadow-lg transition-all duration-300" onClick={() => toggleAccordion(1)}>
            <div className="flex justify-between items-center">
              <h4 className="font-semibold text-lg text-black">Bagaimana cara bergabung?</h4>
              <span className="text-xl">{open === 1 ? '-' : '+'}</span>
            </div>
            <div
              className={`
                overflow-hidden transition-all duration-500 ease-in-out 
                ${open === 1 ? 'max-h-96 opacity-100 scale-y-100' : 'max-h-0 opacity-0 scale-y-50'}
              `}
            >
              {(open === 1 || open === null) && (
                <p className="mt-2 text-sm text-justify text-gray-700">
                  Anda dapat bergabung dengan PKKI dengan mengunjungi situs web kami dan mendaftar untuk mendapatkan akses ke platform dan layanan kami.
                </p>
              )}
            </div>
          </div>

          {/* Accordion Item 3 */}
          <div className="bg-gray p-6 rounded-lg cursor-pointer hover:shadow-lg transition-all duration-300" onClick={() => toggleAccordion(2)}>
            <div className="flex justify-between items-center">
              <h4 className="font-semibold text-lg text-black">Apa manfaat bergabung dengan PKKI?</h4>
              <span className="text-xl">{open === 2 ? '-' : '+'}</span>
            </div>
            <div
              className={`
                overflow-hidden transition-all duration-500 ease-in-out 
                ${open === 2 ? 'max-h-96 opacity-100 scale-y-100' : 'max-h-0 opacity-0 scale-y-50'}
              `}
            >
              {(open === 2 || open === null) && (
                <p className="mt-2 text-sm text-justify text-gray-700">
                  Bergabung dengan PKKI memberi Anda akses ke berbagai sumber daya, inovasi, dan peluang untuk mengembangkan karya intelektual melalui sistem manajemen berbasis TI.
                </p>
              )}
            </div>
          </div>

          {/* Accordion Item 4 */}
          <div className="bg-gray p-6 rounded-lg cursor-pointer hover:shadow-lg transition-all duration-300" onClick={() => toggleAccordion(3)}>
            <div className="flex justify-between items-center">
              <h4 className="font-semibold text-lg text-black">Bagaimana cara melaporkan inovasi?</h4>
              <span className="text-xl">{open === 3 ? '-' : '+'}</span>
            </div>
            <div
              className={`
                overflow-hidden transition-all duration-500 ease-in-out 
                ${open === 3 ? 'max-h-96 opacity-100 scale-y-100' : 'max-h-0 opacity-0 scale-y-50'}
              `}
            >
              {(open === 3 || open === null) && (
                <p className="mt-2 text-sm text-justify text-gray-700">
                  Anda bisa melaporkan inovasi melalui platform online kami dengan mengunggah dokumen terkait dan informasi yang diperlukan untuk verifikasi.
                </p>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Faq;
