import React, { useState } from "react";
import BannerCustom from "../components/HeroCustom";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

const NewsPage = () => {
  const newsData = [
    { 
      title: "PKKI Gelar Workshop Nasional", 
      description: "PKKI menyelenggarakan workshop nasional untuk meningkatkan kompetensi para praktisi di bidang kesehatan...",
      image: "https://placehold.co/300x400"
    },
    { 
      title: "Inovasi Terbaru di Bidang Kesehatan", 
      description: "Teknologi terbaru dalam bidang kesehatan telah berhasil dikembangkan oleh tim peneliti PKKI...",
      image: "https://placehold.co/300x400"
    },
    { 
      title: "Kerjasama Internasional PKKI", 
      description: "PKKI menandatangani kerjasama dengan beberapa institusi kesehatan internasional untuk...",
      image: "https://placehold.co/300x400"
    },
    { 
      title: "Program Kesehatan Masyarakat", 
      description: "PKKI meluncurkan program baru untuk meningkatkan kesehatan masyarakat di berbagai daerah...",
      image: "https://placehold.co/300x400"
    },
  ];

  return (
    <>
      <Navbar />
      <BannerCustom name="Berita PKKI" />

      <div className="container mx-auto px-4 mt-[200px] mb-20">
        <div className="mb-8">
          <h2 className="font-bold text-2xl mb-4">Berita Terkini</h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {newsData.map((news, index) => (
            <div
              key={index}
              className="rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300"
            >
              <img
                src={news.image}
                alt={news.title}
                className="w-full h-[400px] object-cover"
              />
              <div className="p-4">
                <h3 className="text-lg font-bold mb-2 line-clamp-2">{news.title}</h3>
                <p className="text-sm text-gray-600 mb-3 line-clamp-3">{news.description}</p>
                <a 
                  href="#" 
                  className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Read more...
                </a>
              </div>
            </div>
          ))}
        </div>
      </div>
      <Footer/>
    </>
  );
};

export default NewsPage;
