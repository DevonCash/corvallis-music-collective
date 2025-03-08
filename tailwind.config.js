import daisyui from 'daisyui';
import defaultTheme from 'tailwindcss/defaultTheme';
import typography from '@tailwindcss/typography';
import filamentConfig from './vendor/filament/filament/tailwind.config.preset';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [
      filamentConfig
    ],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/awcodes/filament-table-repeater/resources/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app-modules/**/*.blade.php',
        './app-modules/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Lexend', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    daisyui: {
        theme: 'corvmc',
        themes: [
          {
            corvmc: {
              // Original colors
              primary: "#e5771e",
              "primary-content": "#fff",
              secondary: "#00859b",
              "secondary-content": "#fff",
              accent: "#ffe28a",
              warning: "#ffb500",
              error: "#f84d13",
              "error-content": "#fff",
              "success-content": "#fff",
              "base-100": "#ffffff",
              "base-200": "#f7f7f7",
              "base-300": "#B8DDE1",
    
              // Added essential colors
              "base-content": "#1f2937", // Default text color
              neutral: "#3d4451", // Neutral color for text and borders
              "neutral-content": "#ffffff", // Text color on neutral background
              info: "#3b82f6", // Information color (blue)
              "info-content": "#ffffff", // Text on info background
              success: "#22c55e", // Success color (green)
              "warning-content": "#1f2937", // Text on warning background
              "accent-content": "#1f2937", // Text on accent background
    
              // Additional helpful colors
              "neutral-focus": "#303640", // Darker neutral for focus states
              "base-content-secondary": "#4b5563", // Secondary text color
              "neutral-400": "#9ca3af", // Additional neutral shade for borders/dividers
            },
          },
          {
            corvmcDark: {
              // Dark theme - carefully inverted and adjusted for dark mode
              primary: "#ff8f3d", // Brighter orange for dark mode
              "primary-content": "#1a1a1a",
    
              secondary: "#00b4cc", // Brighter teal
              "secondary-content": "#1a1a1a",
    
              accent: "#ffd766", // Warmer yellow
              "accent-content": "#1a1a1a",
    
              warning: "#ffcc33",
              "warning-content": "#1a1a1a",
    
              error: "#ff6347", // Tomato red
              "error-content": "#1a1a1a",
    
              success: "#4ade80", // Brighter green
              "success-content": "#1a1a1a",
    
              info: "#60a5fa", // Brighter blue
              "info-content": "#1a1a1a",
    
              // Dark mode background layers
              "base-100": "#1a1a1a", // Main background
              "base-200": "#2d2d2d", // Lighter background
              "base-300": "#1E2C2E", // Darkened for better speaker cone contrast          // Text colors
              "base-content": "#e6e6e6", // Primary text
              "base-content-secondary": "#a3a3a3", // Secondary text
    
              // Neutral colors
              neutral: "#3d4451",
              "neutral-content": "#e6e6e6",
              "neutral-focus": "#606774",
              "neutral-400": "#6b7280", // Adjusted for dark mode visibility
    
              // Additional dark mode specific adjustments
              "btn-text-case": "normal", // Optional: prevents all-caps buttons
              "--rounded-btn": "0.5rem", // Optional: consistent rounding
              "--animation-btn": "0.25s", // Optional: snappy animations
    
              // Ensures proper focus visibility in dark mode
              "--focus-ring": "2px",
              "--focus-ring-offset": "2px",
              "--focus-ring-color": "#ff8f3d40",
            },
          },
          "light",
          "dark",
          "cupcake",
          "bumblebee",
          "emerald",
          "corporate",
          "synthwave",
          "retro",
          "cyberpunk",
          "valentine",
          "halloween",
          "garden",
          "forest",
          "aqua",
          "lofi",
          "pastel",
          "fantasy",
          "wireframe",
          "black",
          "luxury",
          "dracula",
          "cmyk",
          "autumn",
          "business",
          "acid",
          "lemonade",
          "night",
          "coffee",
          "winter",
          "dim",
          "nord",
          "sunset",
        ],
      },
    plugins: [
        daisyui,
        typography
    ],
};
