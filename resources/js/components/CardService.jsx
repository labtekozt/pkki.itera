import { Lightbulb, Wrench, Tag, Copyright, Palette } from 'lucide-react';

const ServiceCard = ({ title, subtitle, link, icon: Icon, className = "" }) => {
    return (
        <div className={`bg-[#B82132] text-white px-8 py-10 rounded-lg text-center transition-transform duration-300 hover:scale-105 ${className}`}>
            <div className="flex justify-center mb-4">
                <Icon size={48} strokeWidth={1.5} className="text-white" />
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
        <div className="container mx-auto px-4 relative z-10 mt-[-50px] flex flex-col items-center">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl w-full">
                <ServiceCard
                    icon={Lightbulb}
                    title="Paten"
                    subtitle="Lihat Detail"
                    link="/kegiatan"
                />
                <ServiceCard
                    icon={Wrench}
                    title="Paten Sederhana"
                    subtitle="Lihat Detail"
                    link="/kegiatan"
                />
                <ServiceCard
                    icon={Tag}
                    title="Merek Dagang"
                    subtitle="Lihat Detail"
                    link="/kegiatan"
                />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-5 w-full max-w-5xl mt-5">
                <ServiceCard
                    icon={Copyright}
                    title="Hak Cipta"
                    subtitle="Lihat Detail"
                    link="/kegiatan"
                />
                <ServiceCard
                    icon={Palette}
                    title="Desain Industri"
                    subtitle="Lihat Detail"
                    link="/kegiatan"
                />
            </div>
        </div>
    );
};

export default ServiceCards;