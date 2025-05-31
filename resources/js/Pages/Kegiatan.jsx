import React, { useState } from "react";
import BannerCustom from "../components/HeroCustom";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

const sidebarItems = [
  "Paten",
  "Pengenalan Merek",
  "Hak Cipta",
  "Desain Industri",
];

// Data konten untuk setiap menu
const contentData = {
  Paten: (
    <>
      <h1 className="text-2xl font-bold mb-4">Paten</h1>
      <div className="space-y-4 text-sm">
        <div>
          <span className="font-bold">Apakah Paten Itu?</span>
          <br />
          Paten adalah hak eksklusif inventor atas invensi di bidang teknologi untuk selama waktu tertentu melaksanakan sendiri atau memberikan persetujuan kepada pihak lain untuk melaksanakan invensinya.
        </div>
        <div>
          <span className="font-bold">Pengertian Invensi</span>
          <br />
          Invensi adalah ide inventor yang dituangkan ke dalam suatu kegiatan pemecahan masalah yang spesifik di bidang teknologi, dapat berupa produk atau proses atau penyempurnaan dan pengembangan produk atau proses.
        </div>
        <div>
          <span className="font-bold">Paten Sederhana</span>
          <br />
          Setiap invensi berupa produk atau alat yang baru dan mempunyai nilai guna praktis dengan bentuk, konfigurasi, konstruksi atau komponennya dapat memperoleh perlindungan hukum dalam bentuk paten sederhana.
        </div>
        <div>
          <span className="font-bold">Perbedaan Paten dan Paten Sederhana</span>
          <ol className="list-decimal ml-5">
            <li>
              Paten diberikan untuk invensi yang baru, mengandung langkah inventif, dan dapat diterapkan dalam industri. Sementara paten sederhana diberikan untuk invensi yang baru, berkembang, mempunyai nilai guna praktis, dan dapat diterapkan dalam industri.
            </li>
            <li>
              Paten sederhana dibatasi dengan satu klaim mandiri, sedangkan paten jumlah klaimnya tidak dibatasi.
            </li>
          </ol>
        </div>
        <div>
          <span className="font-bold">Invensi dapat diberikan jika invensi tersebut</span>
          <ol className="list-decimal ml-5">
            <li>Baru, jika pada saat pengajuan permohonan Paten invensi tersebut tidak sama dengan teknologi yang diungkapkan sebelumnya.</li>
            <li>Mengandung langkah inventif, jika invensi tersebut merupakan hal yang tidak dapat diduga sebelumnya bagi seseorang yang mempunyai keahlian di bidang teknik.</li>
            <li>Dapat diterapkan dalam industri, jika invensi tersebut dapat dilaksanakan dalam industri sebagaimana dimaksud.</li>
          </ol>
        </div>
        <div>
          <span className="font-bold">Masa Perlindungan Paten</span>
          <ol className="list-decimal ml-5">
            <li>Paten diberikan untuk jangka waktu selama 20 tahun sejak tanggal penerimaan permohonan Paten.</li>
            <li>Paten sederhana diberikan untuk jangka waktu 10 tahun sejak tanggal penerimaan permohonan Paten sederhana.</li>
          </ol>
        </div>
        <div>
          <span className="italic text-gray-600">*Contoh surat pernyataan kepemilikan invensi yang sudah diisi*</span>
        </div>
      </div>
    </>
  ),
  "Pengenalan Merek": (
    <>
      <h1 className="text-2xl font-bold mb-4">Pengenalan Merek</h1>
      <p className="text-sm">
        Merek adalah tanda yang berupa gambar, nama, kata, huruf, angka, susunan warna, atau kombinasi dari unsur-unsur tersebut yang memiliki daya pembeda dan digunakan dalam kegiatan perdagangan barang atau jasa.
      </p>
      <p className="mt-2 text-sm">
        Perlindungan merek diberikan untuk jangka waktu 10 tahun dan dapat diperpanjang.
      </p>
    </>
  ),
  "Hak Cipta": (
    <>
      <h1 className="text-2xl font-bold mb-4">Hak Cipta</h1>
      <p className="text-sm">
        Hak cipta adalah hak eksklusif bagi pencipta atau pemegang hak cipta untuk mengumumkan atau memperbanyak ciptaannya atau memberikan izin kepada pihak lain untuk itu.
      </p>
      <p className="mt-2 text-sm">
        Hak cipta berlaku selama hidup pencipta ditambah 70 tahun setelah kematiannya.
      </p>
    </>
  ),
  "Desain Industri": (
    <>
      <h1 className="text-2xl font-bold mb-4">Desain Industri</h1>
      <p className="text-sm">
        Desain industri adalah suatu kreasi tentang bentuk, konfigurasi, atau komposisi garis atau warna atau gabungan dari semuanya yang memberikan kesan estetis dan dapat diwujudkan dalam tiga dimensi atau dua dimensi.
      </p>
      <p className="mt-2 text-sm">
        Perlindungan desain industri diberikan selama 10 tahun dan dapat diperpanjang.
      </p>
    </>
  ),
};

const GuidePage = () => {
  const [activeMenu, setActiveMenu] = useState("Paten");

  return (
    <>
      <Navbar />
      <BannerCustom name="Panduan" />
      <div className="flex items-start py-8 px-20 z-99">
        {/* Sidebar */}
        <div className="w-56 bg-white rounded-l-2xl shadow-md p-4 mr-4">
          <ul>
            {sidebarItems.map((item, idx) => (
              <li
                key={idx}
                onClick={() => setActiveMenu(item)}
                className={`mb-2 px-3 py-2 rounded-md cursor-pointer select-none ${
                  activeMenu === item
                    ? "bg-[#B82132] text-white font-semibold"
                    : "hover:bg-gray-200"
                }`}>
                {item}
              </li>
            ))}
          </ul>
        </div>
        {/* Main Content */}
        <div className="flex-1 bg-white rounded-r-2xl shadow-md p-8">
          {contentData[activeMenu]}
        </div>
      </div>
      <Footer />
    </>
  );
};

export default GuidePage;
