import type { Config } from 'tailwindcss'

export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      zIndex: {
        '9999': '9999',
        '10000': '10000',
        '99999': '99999',
        '100000': '100000',
      },
    },
  },
  plugins: [],
} satisfies Config
