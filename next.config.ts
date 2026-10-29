import type { NextConfig } from "next";

const config: NextConfig = {
  serverExternalPackages: ["@electric-sql/pglite"],
  poweredByHeader: false,
  images: {
    formats: ["image/avif", "image/webp"],
  },
  async redirects() {
    return [
      {
        source: "/about",
        destination: "/",
        permanent: true,
      },
      {
        source: "/our-story",
        destination: "/",
        permanent: true,
      },
    ];
  },
};

export default config;
