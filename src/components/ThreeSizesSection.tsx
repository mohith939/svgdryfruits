"use client";

import Link from "next/link";
import { motion } from "motion/react";
import { Icon } from "./Icon";

interface SizeRow {
  weight: string;
  note: string;
  photo: string;
  alt: string;
  href: string;
}

const SIZES: SizeRow[] = [
  {
    weight: "250 g",
    note: "A first taste, or a small top-up for the jar.",
    photo: "/assets/size-photo-250g.jpg",
    alt: "250g bowl of premium almonds",
    href: "#collection",
  },
  {
    weight: "500 g",
    note: "The everyday size for a household pantry.",
    photo: "/assets/size-photo-500g.jpg",
    alt: "500g bowl of whole roasted cashews",
    href: "#collection",
  },
  {
    weight: "1 kg",
    note: "For the family that finishes a jar quickly.",
    photo: "/assets/size-photo-1kg.jpg",
    alt: "1kg bowl of rich mixed dry fruits",
    href: "#collection",
  },
];

const PILLARS = [
  {
    icon: "leaf",
    line1: "SAME",
    line2: "GREAT QUALITY",
  },
  {
    icon: "scale",
    line1: "CLEAR",
    line2: "WEIGHTS",
  },
  {
    icon: "heart",
    line1: "PERFECT",
    line2: "FOR EVERY NEED",
  },
];

export function ThreeSizesSection() {
  return (
    <section className="sizes-section-v2" aria-labelledby="sizes-title">
      <div className="sizes-v2-container">
        {/* Left Column: Headline, Story & 3 Pillars */}
        <motion.div
          className="sizes-v2-left"
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-40px" }}
          transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
        >
          {/* Kicker */}
          <div className="sizes-v2-kicker">
            <span className="sizes-kicker-dash" aria-hidden="true" />
            <span>SIMPLE BY DESIGN</span>
          </div>

          {/* Headline */}
          <h2 id="sizes-title" className="sizes-v2-headline">
            Three sizes.<br />
            <em className="sizes-headline-italic">No guesswork.</em>
          </h2>

          {/* Copy */}
          <p className="sizes-v2-desc">
            Every pack comes in a fixed weight, so you always know exactly what you’re ordering and what it costs.
          </p>

          {/* 3 Pillar Badges with Hairline Dividers */}
          <div className="sizes-v2-pillars-row" role="list">
            {PILLARS.map((p, idx) => (
              <div key={idx} className="sizes-pillar-wrap" role="listitem">
                <div className="sizes-pillar-item">
                  <div className="sizes-pillar-icon-badge">
                    <Icon name={p.icon} size={24} />
                  </div>
                  <span className="sizes-pillar-label">
                    {p.line1}
                    <br />
                    {p.line2}
                  </span>
                </div>
                {idx < PILLARS.length - 1 && (
                  <div className="sizes-pillar-divider" aria-hidden="true" />
                )}
              </div>
            ))}
          </div>
        </motion.div>

        {/* Right Column: 3 Size Rows with Photos */}
        <div className="sizes-v2-right" role="list">
          {SIZES.map((item, idx) => (
            <motion.div
              key={item.weight}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-30px" }}
              transition={{
                duration: 0.5,
                delay: 0.15 + idx * 0.12,
                ease: [0.22, 1, 0.36, 1],
              }}
            >
              <Link href={item.href} className="sizes-v2-row" role="listitem">
                {/* Photo */}
                <div className="sizes-v2-photo-wrap">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={item.photo}
                    alt={item.alt}
                    loading="lazy"
                    className="sizes-v2-photo"
                  />
                </div>

                {/* Weight */}
                <span className="sizes-v2-weight">{item.weight}</span>

                {/* Vertical Hairline Divider */}
                <div className="sizes-v2-row-divider" aria-hidden="true" />

                {/* Note Description */}
                <p className="sizes-v2-note">{item.note}</p>

                {/* Action Arrow Circle */}
                <div className="sizes-v2-arrow-badge" aria-hidden="true">
                  <span className="sizes-v2-arrow">→</span>
                </div>
              </Link>
              {idx < SIZES.length - 1 && (
                <div className="sizes-v2-horizontal-divider" aria-hidden="true" />
              )}
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
