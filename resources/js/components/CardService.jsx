import { Award, Palette, Tag, MapPin, FileText } from 'lucide-react';

const ServiceCard = ({ title, subtitle, link, icon: Icon, className = "" }) => {
    return (
        <div className={`bg-[#B82132] text-white px-8 py-10 rounded-lg text-center transition-transform duration-300 hover:scale-105 ${className}`}>
            <div className="flex justify-center mb-4">
                <Icon size={48} className="text-white" />
            </div>
            <h4 className="text-xl font-semibold mb-3">{title}</h4>
            <a href={link} className="text-sm text-white underline hover:text-gray-200 transition-colors">
                {subtitle}
            </a>    
        </div>  
    );
};

const ServiceCards = () => {
    return (
        <div className="container mx-auto px-4 relative z-10 mt-[-40px] flex justify-center">
            <div className="max-w-6xl w-full">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <ServiceCard 
                        title="Merk Paten"
                        subtitle="Lihat Detail"
                        link="/kegiatan"
                        icon={Award}
                    />
                    <ServiceCard 
                        title="Desain Industri"
                        subtitle="Lihat Detail"
                        link="/kegiatan"
                        icon={Palette}
                    />
                    <ServiceCard 
                        title="Hak Cipta"
                        subtitle="Lihat Detail"
                        link="/kegiatan"
                        icon={Tag}
                    />
                </div>
                
                {/* Bottom row - 2 cards centered */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                    <ServiceCard 
                        title="Indikasi Geografis"
                        subtitle="Lihat Detail"
                        link="/kegiatan"
                        icon={MapPin}
                    />
                    <ServiceCard 
                        title="DTLST"
                        subtitle="Lihat Detail"
                        link="/kegiatan"
                        icon={FileText}
                    />
                </div>
            </div>
        </div>
    );
};

export default ServiceCards;