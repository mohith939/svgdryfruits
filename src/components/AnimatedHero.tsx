"use client";

import Link from "next/link";
import { motion } from "motion/react";
import { Icon } from "./Icon";

export function AnimatedHero() {
  return (
    <section className="hero-section-v1" id="top" aria-labelledby="hero-title">
      <div className="hero-stage">
        {/* Background Image: High-res original section-1 background */}
        <div className="hero-bg-layer" aria-hidden="true">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src="/assets/section-1-bg.png"
            alt=""
            className="hero-bg-image"
            fetchPriority="high"
          />

          {/* Desktop Rotating Bowl: Aligned to exact coordinates (71.713% X, 50.105% Y) */}
          <motion.div
            className="hero-rotating-bowl-anchor"
            animate={{ rotate: 360 }}
            transition={{
              repeat: Infinity,
              ease: "linear",
              duration: 35,
            }}
          >
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/mixed dry fruits.png"
              alt="Finest mixed dry fruits and nuts"
              className="hero-rotating-bowl-img"
            />
          </motion.div>
        </div>

        {/* Content layer: Left side brand headline and actions */}
        <div className="hero-content-layer">
          <div className="hero-content-wrap">
            <motion.p
              className="hero-kicker"
              initial={{ opacity: 0, y: 14 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
            >
              WELCOME TO SVG DRY FRUITS
            </motion.p>

            <motion.h1
              id="hero-title"
              className="hero-title"
              initial={{ opacity: 0, y: 22 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.1, ease: [0.22, 1, 0.36, 1] }}
            >
              GOOD THINGS,<br />
              KEPT CLOSE.
            </motion.h1>

            <motion.p
              className="hero-description"
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.2, ease: [0.22, 1, 0.36, 1] }}
            >
              Wholesome California almonds, buttery cashews, festive dates, and curated combos. Sourced with care, packed in transparent pantry sizes, delivered fresh to your doorstep.
            </motion.p>

            <motion.div
              className="hero-actions"
              initial={{ opacity: 0, y: 14 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.3, ease: [0.22, 1, 0.36, 1] }}
            >
              <Link className="hero-shop-btn" href="/shop">
                SHOP NOW <span aria-hidden="true">↗</span>
              </Link>
              <Link className="hero-sub-link" href="/combos">
                EXPLORE GIFT COMBOS
              </Link>
            </motion.div>
          </div>
        </div>

        {/* Mobile/Tablet Visual Fallback: Rendered gracefully on smaller viewports */}
        <div className="hero-mobile-visual" aria-hidden="true">
          <div className="hero-mobile-focal">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/hero-leaves-rosette-clean.png"
              alt=""
              className="hero-mobile-rosette"
            />
            <motion.div
              className="hero-mobile-rotating-bowl"
              animate={{ rotate: 360 }}
              transition={{
                repeat: Infinity,
                ease: "linear",
                duration: 35,
              }}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src="/assets/mixed dry fruits.png"
                alt="Finest mixed dry fruits and nuts"
                className="hero-mobile-bowl-img"
              />
            </motion.div>
          </div>
        </div>

        {/* Overlapping White 3-Badge Bar (Bottom Center) */}
        <div className="hero-badge-bar-wrapper">
          <motion.div
            className="hero-badge-bar-card"
            initial={{ opacity: 0, y: 28 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.65, delay: 0.35, ease: [0.22, 1, 0.36, 1] }}
          >
            {/* Badge 1: Premium Quality */}
            <div className="hero-badge-bar-item">
              <div className="hero-badge-bar-icon" aria-hidden="true">
                <Icon name="almonds" size={32} />
              </div>
              <span className="hero-badge-bar-label">Premium Quality</span>
            </div>

            <div className="hero-badge-bar-sep" aria-hidden="true" />

            {/* Badge 2: Prepared In House */}
            <div className="hero-badge-bar-item">
              <div className="hero-badge-bar-icon" aria-hidden="true">
                <Icon name="home-craft" size={30} />
              </div>
              <span className="hero-badge-bar-label">Prepared In House</span>
            </div>

            <div className="hero-badge-bar-sep" aria-hidden="true" />

            {/* Badge 3: Conveniently Packed */}
            <div className="hero-badge-bar-item">
              <div className="hero-badge-bar-icon" aria-hidden="true">
                <Icon name="pouch" size={30} />
              </div>
              <span className="hero-badge-bar-label">Conveniently Packed</span>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
