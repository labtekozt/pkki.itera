import BannerCustom from "../components/HeroCustom";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import { MapPin, Mail, Phone, Instagram } from "lucide-react";

const ContactPage = () => {
  return (
    <>
      <Navbar />
      <BannerCustom name="Kontak Kami" />
      
      {/* Contact Section */}
      <section className="py-20 bg-gradient-to-br from-gray-50 to-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-20">

          <div className="max-w-4xl mx-auto">
            {/* Contact Information */}
            <div className="space-y-8">
              {/* Contact Info Cards */}
              <div className="grid md:grid-cols-2 gap-6">
                <div className="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-shadow duration-300">
                  <div className="flex items-start gap-4">
                    <div className="bg-blue-100 p-3 rounded-lg">
                      <MapPin className="text-blue-600" size={24} />
                    </div>
                    <div>
                      <h4 className="text-lg font-semibold text-gray-900 mb-2">
                        Alamat 
                      </h4>
                      <p className="text-gray-600 leading-relaxed">
                        Gedung Training Center, Jalan Terusan Ryacudu, Way Hui,
                        Kecamatan Jatiagung, Lampung Selatan 35365.
                      </p>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-shadow duration-300">
                  <div className="flex items-start gap-4">
                    <div className="bg-purple-100 p-3 rounded-lg">
                      <Phone className="text-purple-600" size={24} />
                    </div>
                    <div>
                      <h4 className="text-lg font-semibold text-gray-900 mb-2">
                        Call/WhatsApp
                      </h4>
                      <p className="text-gray-600">
                        Mentari : <br />+62 852-8062-0763
                      </p>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-shadow duration-300">
                  <div className="flex items-start gap-4">
                    <div className="bg-green-100 p-3 rounded-lg">
                      <Mail className="text-green-600" size={24} />
                    </div>
                    <div>
                      <h4 className="text-lg font-semibold text-gray-900 mb-2">
                        Email
                      </h4>
                      <p className="text-gray-600">
                         hki@itera.ac.id
                      </p>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-shadow duration-300">
                  <div className="flex items-start gap-4">
                    <div className="bg-pink-100 p-3 rounded-lg">
                      <Instagram className="text-pink-600" size={24} />
                    </div>
                    <div>
                      <h4 className="text-lg font-semibold text-gray-900 mb-2">
                        Instagram
                      </h4>
                      <p className="text-gray-600">
                        @hkiitera
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Google Maps */}
              <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div className="relative">
                  <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3139.646461800945!2d105.3155440737678!3d-5.35533065360931!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e40c30023a9c495%3A0x77f302ebeab5bc07!2sGedung%20Training%20Center%20(TC)%20Itera!5e1!3m2!1sid!2sid!4v1749745104774!5m2!1sid!2sid"
                    width="100%"
                    height="350"
                    style={{ border: 0 }}
                    allowFullScreen=""
                    loading="lazy"
                    className="w-full"
                  ></iframe>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </>
  );
};

export default ContactPage;