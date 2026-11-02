import type { MetadataRoute } from "next";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "SVG Dry Fruits",
    short_name: "SVG Dry Fruits",
    description: "Premium dry fruits, nuts, berries, and gourmet gift hampers.",
    start_url: "/",
    display: "standalone",
    background_color: "#fcfbf7",
    theme_color: "#203c32",
    icons: [
      {
        src: "/assets/svg-dry-fruits-logo.jpg",
        sizes: "192x192",
        type: "image/jpeg",
      },
      {
        src: "/assets/svg-dry-fruits-logo.jpg",
        sizes: "512x512",
        type: "image/jpeg",
      },
    ],
  };
}
