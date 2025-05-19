import React from 'react';
import { Link } from "@inertiajs/inertia-react";

const ServiceCard = ({ title, subtitle, link }) => {
    return (
        <div className="bg-[#B82132] text-white px-20 py-10 rounded-lg text-center transition-transform duration-300 hover:scale-105">
            <h4 className="text-xl font-semibold">{title}</h4>
            <Link to={link} className="text-sm text-white underline mt-2">{subtitle}</Link>
        </div>  
    );
};

const ServiceCards = () => {
    return (
        <div className="container mx-auto px-4 relative z-10 mt-[-70px] flex justify-center">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl">
                <ServiceCard 
                    title="Hak Cipta"
                    subtitle="Lihat Detail"
                    link="/daftar-hak-cipta"
                />
                <ServiceCard 
                    title="Paten"
                    subtitle="Lihat Detail"
                    link="/daftar-hak-paten"
                />
                <ServiceCard 
                    title="Merk"
                    subtitle="Lihat Detail"
                    link="/daftar-hak-merk"
                />
            </div>
        </div>
    );
};

export default ServiceCards;
