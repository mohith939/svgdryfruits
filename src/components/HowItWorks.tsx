"use client";

import { useState } from "react";
import Link from "next/link";
import { motion, AnimatePresence } from "motion/react";

interface StepItem {
  n: string;
  badgeImg: string;
  title: string;
  body: string;
  actionText: string;
  actionHref: string;
}

const steps: StepItem[] = [
  {
    n: "01",
    badgeImg: "/assets/step-badge-1.png",
    title: "Choose your size",
    body: "Pick a product and a pack size of 250 g, 500 g or 1 kg.",
    actionText: "Explore Sizes",
    actionHref: "#collection",
  },
  {
    n: "02",
    badgeImg: "/assets/step-badge-2.png",
    title: "Add to your bag",
    body: "No account needed. Your bag stays with you as you browse.",
    actionText: "View Bag",
    actionHref: "/cart",
  },
  {
    n: "03",
    badgeImg: "/assets/step-badge-3.png",
    title: "Pay securely",
    body: "Checkout with Razorpay. Prices and stock are confirmed on our server.",
    actionText: "Secure Checkout",
    actionHref: "/checkout",
  },
  {
    n: "04",
    badgeImg: "/assets/step-badge-4.png",
    title: "At your doorstep",
    body: "Follow your order with its order number and private tracking link.",
    actionText: "Track Order",
    actionHref: "/track",
  },
];

export function HowItWorks() {
  const [activeStep, setActiveStep] = useState<number>(2); // Default to Step 03 as in the reference image

  return (
    <section className="steps-section" id="how-it-works" aria-labelledby="steps-title">
      <div className="steps-inner-container">
        {/* Section Heading */}
        <motion.div
          className="steps-heading-wrap"
          initial={{ opacity: 0, y: 18 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-40px" }}
          transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
        >
          <p className="steps-kicker">
            <span className="kicker-line" aria-hidden="true" />
            <span>A BETTER WAY TO SHOP</span>
            <span className="kicker-line" aria-hidden="true" />
          </p>
          <h2 id="steps-title" className="steps-headline">
            From pantry to <em className="headline-doorstep">doorstep.</em>
          </h2>
        </motion.div>

        {/* Desktop Wave Timeline & Interactive Columns */}
        <div className="steps-journey-container">
          {/* Connecting Wave SVG Path */}
          <div className="steps-svg-wave" aria-hidden="true">
            <svg viewBox="0 0 1024 100" fill="none" preserveAspectRatio="none">
              <path
                d="M 146 35 C 200 40, 240 50, 272 54 C 310 58, 350 78, 392 90 C 440 96, 475 60, 513 52 C 555 45, 595 40, 633 44 C 680 50, 720 78, 759 86 C 800 92, 840 76, 874 65"
                stroke="#164332"
                strokeWidth="1.5"
                strokeDasharray="none"
              />
              {/* 3 Golden Milestone Beads */}
              <circle
                cx="272"
                cy="54"
                r={activeStep === 0 || activeStep === 1 ? 5.5 : 4}
                fill="#C9A34E"
                stroke="#F6F0E7"
                strokeWidth="2"
                className="step-wave-bead"
              />
              <circle
                cx="513"
                cy="52"
                r={activeStep === 1 || activeStep === 2 ? 5.5 : 4}
                fill="#C9A34E"
                stroke="#F6F0E7"
                strokeWidth="2"
                className="step-wave-bead"
              />
              <circle
                cx="759"
                cy="86"
                r={activeStep === 2 || activeStep === 3 ? 5.5 : 4}
                fill="#C9A34E"
                stroke="#F6F0E7"
                strokeWidth="2"
                className="step-wave-bead"
              />
            </svg>
          </div>

          {/* 4 Interactive Steps Grid */}
          <div className="steps-columns-grid" role="tablist" aria-label="Order process steps">
            {steps.map((s, idx) => {
              const isActive = activeStep === idx;
              return (
                <div
                  key={s.n}
                  className={`step-column-item step-col-${idx + 1} ${isActive ? "step-active-col" : ""}`}
                >
                  {/* Clickable Badge Icon Button */}
                  <button
                    type="button"
                    role="tab"
                    id={`step-tab-${idx}`}
                    aria-selected={isActive}
                    aria-controls={`step-panel-${idx}`}
                    className={`step-badge-wrap ${isActive ? "badge-active" : ""}`}
                    onClick={() => setActiveStep(idx)}
                    title={`Click to operate Step ${s.n}: ${s.title}`}
                  >
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={s.badgeImg} alt={`Step ${s.n} ${s.title}`} className="step-badge-img" />
                    {isActive && (
                      <motion.span
                        className="badge-active-glow"
                        layoutId="badge-active-indicator"
                        transition={{ duration: 0.3, ease: "easeOut" }}
                      />
                    )}
                  </button>

                  {/* Step Content Card (Clickable to switch active state) */}
                  <div
                    id={`step-panel-${idx}`}
                    role="tabpanel"
                    aria-labelledby={`step-tab-${idx}`}
                    className={`step-content-card ${isActive ? "card-highlighted" : ""}`}
                    onClick={() => setActiveStep(idx)}
                  >
                    {isActive && <div className="step-card-connector" aria-hidden="true" />}
                    <span className="step-num-serif">{s.n}</span>
                    <h3 className="step-col-title">{s.title}</h3>
                    <p className="step-col-body">{s.body}</p>

                    {/* Operational CTA Link */}
                    <AnimatePresence>
                      {isActive && (
                        <motion.div
                          className="step-action-row"
                          initial={{ opacity: 0, height: 0, y: 6 }}
                          animate={{ opacity: 1, height: "auto", y: 0 }}
                          exit={{ opacity: 0, height: 0, y: 6 }}
                          transition={{ duration: 0.25 }}
                        >
                          <Link
                            href={s.actionHref}
                            className="step-operate-btn"
                            onClick={(e) => e.stopPropagation()}
                          >
                            <span>{s.actionText}</span>
                            <span aria-hidden="true">→</span>
                          </Link>
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>

                  {/* Hairline vertical divider between columns (except after active card) */}
                  {(idx === 0 || idx === 1 || idx === 2) && (
                    <div className="step-col-divider" aria-hidden="true" />
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}
