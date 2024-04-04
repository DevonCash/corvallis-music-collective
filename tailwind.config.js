/** @type {import('tailwindcss').Config} */
export default {
  content: ["./resources/**/*.blade.php", "./resources/**/*.js"],
  theme: {
    extend: {},
  },
  daisyui: {
    themes: [
      {
        cmc_light: {
          ...require("daisyui/src/theming/themes")["light"],
          primary: "#D77D37",
          secondary: "#e0b446",
          accent: "#3b7577",
          white: "#FDFEF8",
          "base-100": "#FAE3D0",
          "--rounded-box": 0,
          "--rounded-btn": 0,
        },
      },
    ],
  },
  plugins: [
    require("daisyui"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/container-queries"),
  ],
};
