import React from 'react';
import Navbar from '../components/Navbar';
import Hero from '../components/Hero';
import ServiceCards from '../components/CardService';
// import NewsSection from '../components/News';
// import Team from '../components/Team';
import Faq from '../components/Faq';
import Footer from '../components/Footer';
import { Head } from '@inertiajs/inertia-react';

export default function Welcome({ auth }) {
  return (
    <>
      <Head>
        <title>Welcome - PKKI ITERA</title>
        <meta name="description" content="Welcome to PKKI ITERA Application" />
      </Head>
      
      <Navbar/>
      <Hero/>
      <ServiceCards/>
      {/* <NewsSection/> */}
      {/* <Team/> */}
      <Faq/>
      <Footer/>
    </>
  );
}
