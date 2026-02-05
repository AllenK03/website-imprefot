/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Livewire/**/*.php", // Muy importante para que detecte clases en Livewire
  ],
  theme: {
    extend: {
      colors: {
        'impre-blue': '#00BCD4',   // El Cian de tu logo
        'impre-green': '#004D40',  // El Verde oscuro
        'impre-orange': '#FF8F00', // El Naranja vibrante
      },
    },
  },
  plugins: [],
}
