import React from 'react';

const Team = () => {
  return (
    <section className="bg-[#b21f2d] text-white py-16 mt-20 md:px-20">
      <div className="max-w-7xl mx-auto px-4 text-center">
        <h2 className="text-3xl font-bold mb-4">TIM PKKI ITERA</h2>
        <p className="text-sm mb-8">
          Lorem ipsum dolor
        </p>
        <div className="overflow-x-auto">
          <div className="flex gap-6 px-4 py-2 w-max">
            {[...Array(13)].map((_, i) => (
              <div
                key={i}
                className="bg-white text-center text-black p-6 rounded-lg shadow-lg w-64 shrink-0"
              >
                <div className="w-32 h-32 mx-auto bg-gray-200 rounded-full mb-4"></div>
                <h4 className="font-semibold text-lg">John Doe {i + 1}</h4>
                <p className="text-sm">Lorem Ipsum</p>
              </div>
            ))}
          </div>
        </div>

      </div>
    </section>
  );
};

export default Team;
