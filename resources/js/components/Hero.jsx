import { Link } from "@inertiajs/inertia-react";
import heroImage from "../../../public/images/hero.png"; 

const handleClick = () => {
    window.location.href = "/kegiatan";
};

const Hero = () => {
    return (
        <div className="bannerHome h-screen relative -mt-20 md:-mt-24">
            <div className="overlay"></div>
            <img rel="preload" src={heroImage} alt="hero" class="object-cover w-full h-full absolute" loading="lazy" />
            <div className="bannerContent z-10 relative mt-24 md:mt-20 flex-row text-left px-5 md:px-12 mx-4 md:mx-8">
                <h1 className="text-2xl sm:text-2xl md:text-4xl lg:text-4xl text-white mb-2">
                    Selamat Datang <br />
                    <span className="font-bold">Pusat Kelola Karya Intelektual</span> <br />
                    Institut Teknologi Sumatera
                </h1>
                <p className="text-white mt-10 max-w-2xl text-base md:text-md">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras interdum orci tellus, vitae tempor sapien ultrices a. Donec ullamcorper laoreet mauris, nec tincidunt lorem aliquet vitae. Aenean consectetur arcu nulla, vitae feugiat nunc egestas ut. Curabitur vitae molestie turpis, nec pretium diam. Aenean interdum consectetur mauris, sed lobortis lacus vulputate eu. 
                </p>

                <div className="flex space-x-4">
                <div className="flex space-x-4 mt-8">
                    <button
                        onClick={handleClick}
                        className="bg-[#B82132] text-white px-6 py-3 rounded-full hover:bg-red-800 transition duration-300">
                        Login
                    </button>
                </div>
                <div className="flex space-x-4 mt-8">
                    <Link 
                        href="/daftar-hak-paten" 
                        className="bg-[#B82132] text-white px-6 py-3 rounded-full hover:bg-red-800 transition duration-300">
                        Login SSO
                    </Link>
                </div>
              </div>
            </div>
        </div>
    );
};

export default Hero;