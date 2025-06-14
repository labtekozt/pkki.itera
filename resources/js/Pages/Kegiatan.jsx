import React, { useState, Suspense  } from "react";
import BannerCustom from "../components/HeroCustom";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
// const BannerCustom = React.lazy(() => import('../components/HeroCustom'));

const sidebarItems = [
  "Paten",
  "Paten Sederhana",
  "Merek Dagang",
  "Hak Cipta",
  "Desain Industri",
];
// Data konten untuk setiap menu
const contentData = {
  Paten: (
    <>
      <h1 className="text-xl md:text-2xl font-bold mb-4">Paten</h1>
      <div className="space-y-4 text-sm md:text-base">
        <div>
          Paten adalah hak eksklusif yang diberikan oleh negara kepada inventor atas hasil invensinya di bidang teknologi untuk jangka waktu tertentu melaksanakan sendiri invensi tersebut atau memberikan persetujuan kepada pihak lain untuk melaksanakannya.
          <br /><br />
          Teknologi yang dimaksud mencakup semua jenis teknologi, dari teknologi yang bersifat sangat sederhana hingga teknologi canggih yang mutakhir. 
          <br /><br />
          Contohnya bisa berupa teknologi sederhana seperti tusuk gigi hingga teknologi nano dan teknologi Artificial Intelligence (AI), seluruhnya dapat dilindungi dengan sistem paten sepanjang memenuhi persyaratan patentabilitas (baru, inventif dan dapat diterapkan dalam industri) dan ketentuan lain dalam Undang-Undang paten.
        </div>
        <div>
          <span className="font-bold">Pengertian Invensi</span>
          <br />
          Invensi adalah ide inventor yang dituangkan ke dalam suatu kegiatan pemecahan masalah yang spesifik di bidang teknologi, dapat berupa produk atau proses atau penyempurnaan dan pengembangan produk atau proses.
        </div>
        <div>
          <span className="font-bold">Invensi dapat diberikan jika invensi tersebut</span>
          <ol className="list-decimal ml-5 mt-2">
            <li className="mb-2">Baru, jika pada saat pengajuan permohonan Paten invensi tersebut tidak sama dengan teknologi yang diungkapkan sebelumnya.</li>
            <li className="mb-2">Mengandung langkah inventif, jika invensi tersebut merupakan hal yang tidak dapat diduga sebelumnya bagi seseorang yang mempunyai keahlian di bidang teknik.</li>
            <li>Dapat diterapkan dalam industri, jika invensi tersebut dapat dilaksanakan dalam industri sebagaimana dimaksud.</li>
          </ol>
        </div>
        <div>
          <span className="font-bold">Masa Perlindungan Paten</span>
          <ol className="list-decimal ml-5 mt-2">
            <li className="mb-2">Paten diberikan untuk jangka waktu selama 20 tahun sejak tanggal penerimaan permohonan Paten.</li>
            <li>Paten sederhana diberikan untuk jangka waktu 10 tahun sejak tanggal penerimaan permohonan Paten sederhana.</li>
          </ol>
        </div>
        <div>
          <span className="italic text-gray-600">*Contoh surat pernyataan kepemilikan invensi yang sudah diisi*</span>
        </div>
      </div>
    </>
  ),
  "Paten Sederhana": (
    <>
      <h1 className="text-xl md:text-2xl font-bold mb-4">Paten Sederhana</h1>
      <div>
          Paten adalah hak eksklusif yang diberikan oleh negara kepada inventor atas hasil invensinya di bidang teknologi untuk jangka waktu tertentu melaksanakan sendiri invensi tersebut atau memberikan persetujuan kepada pihak lain untuk melaksanakannya.
          <br /><br />
          Teknologi yang dimaksud mencakup semua jenis teknologi, dari teknologi yang bersifat sangat sederhana hingga teknologi canggih yang mutakhir. 
          <br /><br />
          Contohnya bisa berupa teknologi sederhana seperti tusuk gigi hingga teknologi nano dan teknologi Artificial Intelligence (AI), seluruhnya dapat dilindungi dengan sistem paten sepanjang memenuhi persyaratan patentabilitas (baru, inventif dan dapat diterapkan dalam industri) dan ketentuan lain dalam Undang-Undang paten.
        </div>
        <div>
          <br />
          <h2 className="font-bold">Paten Sederhana</h2>
          Paten sederhana secara khusus  Diberikan untuk invensi yang baru, pengembangan dari produk atau proses yang telah ada, dan dapat diterapkan dalam industri. Cakupan kalimnya hanya Produk atau proses/metode. Jumlah klaim Hanya terdapat 1 (satu) klaim mandiri dalam satu
          kesatuan invensi. Pengumumannya akan dilakukan 3 (tiga) Bulan setelah tanggal penerimaan dengan jangka waktu pengumuman Selama 2 (dua) bulan. Pemeriksaan Substansif dilaksanakan maksimum 7 (tujuh) Bulan sejak berakhirnya masa pengumuman. Jangka waktu perlindungan yaitu 10 (sepuluh) tahun sejak tanggal penerimaan.
        </div>
        <div>
          <br />
          <span className="font-bold">Pengertian Invensi</span>
          <br />
          Invensi adalah ide inventor yang dituangkan ke dalam suatu kegiatan pemecahan masalah yang spesifik di bidang teknologi, dapat berupa produk atau proses atau penyempurnaan dan pengembangan produk atau proses.
        </div>
        <div>
          <br />
          <span className="font-bold">Invensi dapat diberikan jika invensi tersebut</span>
          <ol className="list-decimal ml-5 mt-2">
            <li className="mb-2">Baru, jika pada saat pengajuan permohonan Paten invensi tersebut tidak sama dengan teknologi yang diungkapkan sebelumnya.</li>
            <li className="mb-2">Mengandung langkah inventif, jika invensi tersebut merupakan hal yang tidak dapat diduga sebelumnya bagi seseorang yang mempunyai keahlian di bidang teknik.</li>
            <li>Dapat diterapkan dalam industri, jika invensi tersebut dapat dilaksanakan dalam industri sebagaimana dimaksud.</li>
          </ol>
        </div>
        <div>
          <br />
          <span className="font-bold">Masa Perlindungan Paten</span>
          <ol className="list-decimal ml-5 mt-2">
            <li className="mb-2">Paten diberikan untuk jangka waktu selama 20 tahun sejak tanggal penerimaan permohonan Paten.</li>
            <li>Paten sederhana diberikan untuk jangka waktu 10 tahun sejak tanggal penerimaan permohonan Paten sederhana.</li>
          </ol>
        </div>
        <div>
          <span className="italic text-gray-600">*Contoh surat pernyataan kepemilikan invensi yang sudah diisi*</span>
        </div>
    </>
  ),
  "Merek Dagang": (
    <>
      <h1 className="text-xl md:text-2xl font-bold mb-4">Pengenalan Merek</h1>
      <div className="space-y-4 text-sm md:text-base">
        <p>
          Merek adalah tanda yang dapat ditampilkan secara grafis berupa gambar, logo, nama, kata, huruf, angka, susunan warna, dalam bentuk 2 (dua) dimensi dan/atau 3 (tiga) dimensi, suara, hologram, atau kombinasi dari 2 (dua) atau lebih unsur tersebut untuk membedakan barang dan/atau jasa yang diproduksi oleh orang atau badan hukum dalam kegiatan perdagangan barang dan/atau jasa.
          <br />
          Pemakaian Merek berfungsi sebagai:
          <ol className="list-decimal ml-5 mt-2">
            <li>Tanda pengenal untuk membedakan hasil produksi yang dihasilkan seseorang atau beberapa orang secara bersama-sama atau badan hukum dengan produksi orang lain atau badan hukum lainnya;</li>
            <li>Alat promosi, sehingga mempromosikan hasil produksinya cukup dengan menyebut Mereknya;</li>
            <li>Jaminan atas mutu barangnya;</li>
            <li>Penunjuk asal barang/jasa dihasilkan.</li>
          </ol>
        </p>
        <p>
          Pendaftaran Merek berfungsi sebagai:
          <ol className="list-decimal ml-5 mt-2">
            <li>Alat bukti bagi pemilik yang berhak atas Merek yang didaftarkan;</li>
            <li>Dasar penolakan terhadap Merek yang sama keseluruhan atau sama pada pokoknya yang dimohonkan pendaftaran oleh orang lain untuk barang/jasa sejenisnya;</li>
            <li>Dasar untuk mencegah orang lain memakai Merek yang sama keseluruhan atau  sama  pada pokoknya  dalam  peredaran  untuk barang/jasa sejenisnya.</li>
          </ol>
        </p>
        <p>
          Adapun merek yang bisa didaftarkan yaitu :
          <ol className="list-decimal ml-5 mt-2">
            <li>bertentangan dengan ideologi negara, peraturan perundang-undangan, moralitas, agama, kesusilaan, atau ketertiban umum;</li>
            <li>sama dengan, berkaitan dengan, atau hanya menyebut barang dan/atau jasa yang dimohonkan pendaftarannya;</li>
            <li>memuat unsur yang dapat menyesatkan masyarakat tentang asal, kualitas, jenis, ukuran, macam, tujuan penggunaan barang dan/atau jasa yang dimohonkan pendaftarannya atau merupakan nama varietas tanaman yang dilindungi untuk barang dan/atau jasa yang sejenis;</li>
            <li>memuat keterangan yang tidak sesuai dengan kualitas, manfaat, atau khasiat dari barang dan/atau jasa yang diproduksi;</li>
            <li>tidak memiliki daya pembeda; dan/atau</li>
            <li>merupakan nama umum dan/atau lambang milik umum.</li>
          </ol>
        </p>
        <p>
          Permohonan pendaftaran Merek ditolak apabila Merek tersebut:
          <ol className="list-decimal ml-5 mt-2">
            <li>mempunyai persamaan pada pokoknya atau keseluruhannya dengan Merek milik pihak lain yang sudah terdaftar lebih dahulu untuk barang dan/atau jasa yang sejenis;</li>
            <li>mempunyai persamaan pada pokoknya atau keseluruhannya dengan Merek yang sudah terkenal milik pihak lain untuk barang dan/atau jasa sejenis;</li>
            <li>mempunyai persamaan pada pokoknya atau keseluruhannya dengan Merek yang sudah terkenal milik pihak lain untuk barang dan/atau jasa tidak sejenis sepanjang memenuhi persyaratan tertentu yang ditetapkan lebih lanjut dengan peraturan pemerintah;</li>
            <li>mempunyai persamaan pada pokoknya atau keseluruhannya dengan indikasi-geografis yang sudah dikenal;</li>
            <li>merupakan atau menyerupai nama orang terkenal, foto, atau nama badan hukum yang dimiliki orang lain, kecuali atas persetujuan tertulis dari yang berhak;</li>
            <li>merupakan tiruan atau menyerupai nama atau singkatan nama, bendera, lambang atau simbol atau emblem negara atau lembaga nasional maupun internasional, kecuali atas persetujuan tertulis dari pihak yang berwenang;</li>
            <li>merupakan tiruan atau menyerupai tanda atau cap atau stempel resmi yang digunakan oleh Negara atau lembaga pemerintah, kecuali atas persetujuan tertulis dari pihak yang berwenang.</li>
          </ol>
        </p>
        <p>
          Merek terdaftar mendapatkan perlindungan hukum untuk jangka waktu 10 tahun sejak tanggal penerimaan permohonan pendaftaran Merek yang bersangkutan dan jangka waktu perlindungan itu dapat diperpanjang.
        </p>
      </div>
    </>
  ),
  "Hak Cipta": (
    <>
      <h1 className="text-xl md:text-2xl font-bold mb-4">Hak Cipta</h1>
      <div className="space-y-4 text-sm md:text-base">
        <p>
          Pelindungan hak cipta tidak diberikan kepada ide atau gagasan tetapi diberikan kepada wujud dari ide, oleh karena itu Ciptaan harus memiliki bentuk yang khas bersifat pribadi dan menunjukkan keaslian sebagai ciptaan yang lahir karena kreativitas, atau keahlian sehingga ciptaan itu dapat dilihat, dibaca atau didengar. Oleh karena itu dalam Undang-Undang Nomor 28 Tahun 2014 Tentang Hak Cipta (Undang-Undang Hak Cipta) menyebutkan bahwa Hak Cipta adalah hak eksklusif pencipta yang timbul secara otomatis berdasarkan prinsip deklaratif setelah suatu ciptaan diwujudkan dalam bentuk nyata tanpa mengurangi pembatasan sesuai dengan ketentuan peraturan perundang- undangan dalam persetujuan TRIPs dipertegas bahwa pelindungan hak cipta meliputi ekspresi dan tidak meliputi ide, prosedur, metode kerja atau konsep matematis sejenisnya.
          Hak Cipta adalah hak eksklusif pencipta yang timbul secara otomatis berdasarkan prinsip deklaratif setelah suatu ciptaan diwujudkan dalam bentuk nyata tanpa mengurangi pembatasan sesuai dengan ketentuan peraturan perundang-undangan.
          Hak Terkait itu adalah hak yang berkaitan dengan Hak Cipta yang merupakan hak eksklusif bagi pelaku pertunjukan, produser fonogram, atau lembaga penyiaran.
          <br />
          <br />
          Ciptaan yang di lindungi:
          <ol className="list-decimal ml-5 mt-2">
            <li>Buku, program komputer, pamflet, perwajahan (layout) karya tulis yang diterbitkan, dan semua hasil karya tulis lain;</li>
            <li>Ceramah, kuliah, pidato, dan ciptaan lain yang sejenis dengan itu;</li>
            <li>Alat peraga yang dibuat untuk kepentingan pendidikan dan ilmu pengetahuan;</li>
            <li>Lagu atau musik dengan atau tanpa teks;</li>
            <li>Drama atau drama musikal, tari, koreografi, pewayangan, dan pantomim;</li>
            <li>Seni rupa dalam segala bentuk seperti seni lukis, gambar, seni ukir, seni kaligrafi, seni pahat, seni patung, kolase, dan seni terapan;</li>
            <li>Arsitektur;</li>
            <li>Peta;</li>
            <li>Seni Batik;</li>
            <li>Fotografi;</li>
            <li>Terjemahan, tafsir, saduran, bunga rampai, dan karya lain dari hasil pengalihwujudan.</li>
          </ol>
        </p>
        <p>
          Masa perlindungan hak cipta : 
          <ol className="list-decimal ml-5 mt-2">
            <li>Perlindungan Hak Cipta : Seumur Hidup Pencipta + 70 Tahun.</li>
            <li>Program Komputer : 50 tahun Sejak pertama kali dipublikasikan.</li>
            <li>Pelaku : 50 tahun sejak pertama kali dipertunjukkan.</li>
            <li>Produser Rekaman : 50 tahun sejak Ciptaan difiksasikan.</li>
            <li>Lembaga Penyiaran : 20 tahun sejak pertama kali disiarkan.</li>
          </ol>
        </p>
      </div>
    </>
  ),
  "Desain Industri": (
    <>
      <h1 className="text-xl md:text-2xl font-bold mb-4">Desain Industri</h1>
      <div className="space-y-4 text-sm md:text-base">
        <p>
          Desain Industri adalah suatu kreasi tentang bentuk, konfigurasi atau komposisi garis atau warna, atau garis dan warna, atau gabungan daripadanya yang berbentuk tiga dimensi atau dua dimensi yang memberikan kesan estetis dan dapat diwujudkan dalam pola tiga dimensi atau dua dimensi serta dapat dipakai untuk menghasilkan suatu produk, barang, komoditas industri atau kerajinan tangan.
          <br />
          Adapun Desain Industri yang dapat didaftarkan yaitu:
          <ol className="list-decimal ml-5 mt-2">
            <li>Desain Industri yang memiliki kebaruan (novelty) dengan catatan jika pada tanggal penerimaan permohonan pendaftaran Desain Industri tersebut tidak sama dengan pengungkapan Desain Industri yang telah ada sebelumnya;</li>
            <li>Tidak bertentangan dengan peraturan perundang-undangan yang berlaku, ketertiban umum, agama, atau kesusilaan.</li>
          </ol>
        </p>
        <p>
          Masa pelindungan desain industri adalah 10 tahun sejak tanggal penerimaan. Pemegang hak desain industri memiliki hak eksklusif untuk melaksanakan hak yang dimilikinya dan untuk melarang orang lain tanpa persetujuannya membuat, memakai, menjual, mengimpor, mengekspor, dan/atau mengedarkan produk-produk terkait.
        </p>
      </div>
    </>
  ),
};

const GuidePage = () => {
  const [activeMenu, setActiveMenu] = useState("Paten");
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  return (
    <>
      <Navbar />
      <BannerCustom name="Panduan" />
      
      {/* Mobile Menu Button */}
      <div className="md:hidden flex justify-between items-center px-10 py-4 bg-gray-50 mt-[150px]">
        <h2 className="text-lg font-semibold">Menu Panduan</h2>
        <button
          onClick={() => setIsSidebarOpen(!isSidebarOpen)}
          className="bg-[#B82132] text-white px-4 py-2 rounded-md text-sm"
        >
          {isSidebarOpen ? 'Tutup' : 'Buka Menu'}
        </button>
      </div>

      <div className="flex flex-col md:flex-row items-start py-4 md:py-8 px-4 md:px-8 lg:px-20">
        {/* Sidebar */}
        <div className={`
          ${isSidebarOpen ? 'block' : 'hidden'} md:block
          w-full md:w-56 bg-white rounded-lg md:rounded-l-2xl shadow-md p-4 mb-4 md:mb-0 md:mr-4 z-40 relative
        `}>
          <ul>
            {sidebarItems.map((item, idx) => (
              <li
                key={idx}
                onClick={() => {
                  setActiveMenu(item);
                  setIsSidebarOpen(false); 
                }}
                className={`mb-2 px-3 py-2 rounded-md cursor-pointer select-none text-sm md:text-base ${
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
        <div className="flex-1 bg-white rounded-lg md:rounded-r-2xl shadow-md p-4 md:p-6 lg:p-8">
          {contentData[activeMenu]}
        </div>
      </div>

      {/* Mobile Sidebar Overlay */}
      {isSidebarOpen && (
        <div 
          className="md:hidden fixed inset-0 bg-black bg-opacity-50 z-30"
          onClick={() => setIsSidebarOpen(false)}
        ></div>
      )}

      <Footer />
    </>
  );
};

export default GuidePage;