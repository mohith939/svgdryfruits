"use client";

import { motion } from "motion/react";
import { Icon } from "./Icon";

export function WhatsAppButton() {
  const rawNumber = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER;
  const cleaned = rawNumber ? rawNumber.replace(/\D/g, "") : "";
  // If no number configured in dev, link to WhatsApp with prefilled message
  const whatsappUrl = cleaned
    ? `https://wa.me/${cleaned}?text=Hello%20SVG%20Dry%20Fruits%2C%20I%20have%20an%20enquiry.`
    : `https://wa.me/?text=Hello%20SVG%20Dry%20Fruits%2C%20I%20have%20an%20enquiry%20regarding%20dry%20fruits.`;

  return (
    <div className="wa-fixed-wrapper">
      {/* Animated gentle glowing pulse ripple */}
      <motion.span
        className="wa-pulse-ring"
        animate={{
          scale: [1, 1.35, 1],
          opacity: [0.6, 0, 0.6],
        }}
        transition={{
          duration: 2.4,
          repeat: Infinity,
          ease: "easeInOut",
        }}
      />

      <motion.a
        href={whatsappUrl}
        target="_blank"
        rel="noopener noreferrer"
        className="wa-floating-btn"
        aria-label="Chat with SVG Dry Fruits on WhatsApp"
        title="Chat with us on WhatsApp"
        initial={{ opacity: 0, scale: 0.6, y: 30 }}
        animate={{
          opacity: 1,
          scale: 1,
          y: [0, -5, 0],
        }}
        transition={{
          opacity: { duration: 0.4 },
          scale: { duration: 0.4 },
          y: {
            duration: 3,
            repeat: Infinity,
            ease: "easeInOut",
          },
        }}
        whileHover={{
          scale: 1.08,
          boxShadow: "0 14px 34px rgba(7, 61, 38, 0.55)",
          transition: { duration: 0.2 },
        }}
        whileTap={{ scale: 0.94 }}
      >
        <span className="wa-icon-wrap">
          <Icon name="whatsapp" size={28} />
          <span className="wa-online-dot" aria-hidden="true" />
        </span>
        <span className="wa-label-text">Chat with us</span>
      </motion.a>
    </div>
  );
}
