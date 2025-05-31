/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class", // Mengaktifkan dark mode berdasarkan kelas 'dark'
    presets: [
      // Menyertakan preset dari Filament jika diperlukan
      require('./vendor/filament/support/tailwind.config.preset')
    ],
    content: [
      './app/Filament/**/*.php', // Menargetkan semua file PHP di folder Filament
      './resources/views/filament/**/*.blade.php', // Menargetkan Blade views yang terkait dengan Filament
      './vendor/filament/**/*.blade.php', // Menargetkan Blade views yang ada di dalam vendor Filament
      './resources/js/**/*.{js,jsx,ts,tsx}', // Menargetkan file JS/JSX/TSX di folder resources/js untuk React (Inertia)
    ],
    theme: {
      screens: {
        sm: "576px",
        // => @media (min-width: 576px) { ... }
  
        md: "768px",
        // => @media (min-width: 768px) { ... }
  
        lg: "992px",
        // => @media (min-width: 992px) { ... }
  
        xl: "1200px",
        // => @media (min-width: 1200px) { ... }
  
        xxl: "1400px",
        // => @media (min-width: 1400px) { ... }
      },
      extend: {
        fontFamily: {
          // Add your custom fonts
          dmSans: ["DM Sans", "sans-serif"],
          clashDisplay: ["Clash Display", "sans-serif"],
          raleway: ["Raleway", "sans-serif"],
          spaceGrotesk: ["Space Grotesk", "sans-serif"],
          plusJakarta: ["Plus Jakarta Sans", "sans-serif"],
          manrope: ["Manrope", "sans-serif"],
          body: ["Inter", "sans-serif"],
        },
  
        colors: {
          gray: "#d3d3d3",
          colorCodGray: "#191919",
          colorOrangyRed: "#FE330A",
          colorLinenRuffle: "#EFEAE3",
          colorViolet: "#321CA4",
          colorGreen: "#8eec31",
          darkGreen: "#219c0b",
        },
      },
    },
    plugins: [],
  }