"use client";

import { useEffect } from "react";

// Adds scroll-reveal motion to marked sections; skipped when the user prefers reduced motion.
export function Motion() {
  useEffect(() => {
    if (!("IntersectionObserver" in window) || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    document.documentElement.classList.add("motion-ready");
    const io = new IntersectionObserver((entries, o) => {
      for (const e of entries) if (e.isIntersecting) { e.target.classList.add("is-visible"); o.unobserve(e.target); }
    }, { threshold: 0.12 });
    document.querySelectorAll(".pantry-section, .gifting-section, .story-section, .catalog-card, .shop-note").forEach((el) => {
      el.classList.add("motion-reveal");
      io.observe(el);
    });
    return () => io.disconnect();
  });
  return null;
}
