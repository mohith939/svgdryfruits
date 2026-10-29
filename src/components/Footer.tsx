"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Icon } from "./Icon";
import { instagramProfile } from "@/lib/social";

const shopLinks = [
  { href: "/categories/nuts", label: "Nuts" },
  { href: "/categories/dates-dried-fruit", label: "Dried Fruits" },
  { href: "/shop", label: "Seeds" },
  { href: "/shop", label: "Healthy Mix" },
  { href: "/combos", label: "Combos" },
];

const helpLinks = [
  { href: "/track", label: "Track Order" },
  { href: "/shipping-policy", label: "Shipping Info" },
  { href: "/return-policy", label: "Returns & Refunds" },
  { href: "/#faq", label: "FAQs" },
  { href: "/contact", label: "Contact Us" },
];

const exploreLinks = [
  { href: "/#why-us", label: "Quality Promise" },
  { href: "/#faq", label: "FAQs" },
  { href: "/combos", label: "Gifting & Combos" },
  { href: "/contact", label: "Bulk Inquiries" },
  { href: "/contact", label: "Contact Us" },
];

export function Footer() {
  const pathname = usePathname();

  if (pathname?.startsWith("/admin")) {
    return null;
  }

  const wa = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER?.replace(/\D/g, "") || "918340956469";
  const ig = instagramProfile() || "https://instagram.com";

  return (
    <footer className="site-footer-v2" aria-labelledby="footer-brand-title">
      {/* =====================================================================
          SINGLE FOOTER BACKGROUND IMAGE
          ===================================================================== */}
      <div className="footer-bg-layer" aria-hidden="true">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src="/assets/footer bg.png"
          alt=""
          className="footer-bg-img"
        />
      </div>

      {/* =====================================================================
          MAIN FOOTER CONTENT
          ===================================================================== */}
      <div className="footer-main-container">
        <div className="footer-top-grid">
          {/* Brand Column */}
          <div className="footer-brand-col">
            <div className="footer-brand-header">
              <div className="footer-brand-title-wrap">
                {/* SVG Dry Fruits Logo */}
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src="/assets/svg-dry-fruits-logo.jpg"
                  alt="SVG Dry Fruits Logo"
                  className="footer-svg-logo"
                />
                <span className="footer-brand-kicker">SVG DRY FRUITS</span>
              </div>
            </div>

            <h2 id="footer-brand-title" className="footer-tagline-v2">
              Good Things,<br />
              <em className="footer-tagline-italic">Kept Close.</em>
            </h2>

            {/* Circular Outlined Social Media Buttons */}
            <div className="footer-social-row" aria-label="Social links">
              <a
                href={ig || "https://instagram.com/svgdryfruits"}
                target="_blank"
                rel="noopener noreferrer"
                className="footer-social-btn footer-social-instagram"
                aria-label="Follow SVG Dry Fruits on Instagram"
              >
                <Icon name="instagram" size={18} />
              </a>
              <a
                href="https://facebook.com/svgdryfruits"
                target="_blank"
                rel="noopener noreferrer"
                className="footer-social-btn footer-social-facebook"
                aria-label="Follow SVG Dry Fruits on Facebook"
              >
                <Icon name="facebook" size={18} />
              </a>
              <a
                href="https://youtube.com/@svgdryfruits"
                target="_blank"
                rel="noopener noreferrer"
                className="footer-social-btn footer-social-youtube"
                aria-label="Subscribe to SVG Dry Fruits on YouTube"
              >
                <Icon name="youtube" size={18} />
              </a>
              <a
                href={`https://wa.me/${wa}`}
                target="_blank"
                rel="noopener noreferrer"
                className="footer-social-btn footer-social-whatsapp"
                aria-label="Chat with SVG Dry Fruits on WhatsApp"
              >
                <Icon name="whatsapp" size={18} />
              </a>
            </div>
          </div>

          {/* Nav: SHOP */}
          <nav className="footer-nav-col" aria-label="Shop categories">
            <h3 className="footer-col-heading">SHOP</h3>
            <ul className="footer-links-list">
              {shopLinks.map((item) => (
                <li key={item.label}>
                  <Link href={item.href} className="footer-nav-link">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          {/* Nav: HELP */}
          <nav className="footer-nav-col" aria-label="Help and support">
            <h3 className="footer-col-heading">HELP</h3>
            <ul className="footer-links-list">
              {helpLinks.map((item) => (
                <li key={item.label}>
                  <Link href={item.href} className="footer-nav-link">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          {/* Nav: EXPLORE */}
          <nav className="footer-nav-col" aria-label="Explore SVG Dry Fruits">
            <h3 className="footer-col-heading">EXPLORE</h3>
            <ul className="footer-links-list">
              {exploreLinks.map((item) => (
                <li key={item.label}>
                  <Link href={item.href} className="footer-nav-link">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>
        </div>

        {/* =====================================================================
            HIGHLIGHTED FINSTICS PARTNERSHIP SPOTLIGHT (TOP)
            ===================================================================== */}
        <div className="footer-finstics-spotlight-row">
          <div className="footer-finstics-wrapper">
            <div className="footer-finstics-card">
              <div className="footer-finstics-brand">
                <span className="footer-finstics-label">Designed and Developed by</span>
                <a
                  href="https://finstics.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="footer-finstics-logo-link"
                  aria-label="Finstics website"
                >
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src="/assets/finstics logo.png"
                    alt="Finstics"
                    className="footer-finstics-logo-img"
                  />
                </a>
              </div>

              <div className="footer-finstics-divider" aria-hidden="true" />

              <div className="footer-finstics-contact">
                <div className="footer-finstics-support-text">
                  <span className="footer-finstics-headset-icon" aria-hidden="true">
                    <Icon name="headset" size={14} />
                  </span>
                  <span>For any website related works</span>
                </div>
                <a
                  href="tel:+918340956469"
                  className="footer-finstics-call-btn"
                  aria-label="Call Finstics at +91 8340956469"
                >
                  <Icon name="phone" size={12} />
                  <span>+91 8340956469</span>
                </a>
              </div>
            </div>

            {/* Playful Hand-Drawn Curved Arrow Doodle */}
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/footer-arrow-doodle.png"
              alt=""
              className="footer-finstics-arrow"
              aria-hidden="true"
            />
          </div>
        </div>

        {/* =====================================================================
            BOTTOM COPYRIGHT & LEGAL LINKS (BELOW)
            ===================================================================== */}
        <div className="footer-bottom-bar">
          <p className="footer-copyright">
            © 2026 SVG Dry Fruits. All rights reserved.
          </p>

          <div className="footer-legal-links">
            <Link href="/privacy-policy" className="footer-legal-link">
              Privacy Policy
            </Link>
            <span className="footer-legal-sep" aria-hidden="true">|</span>
            <Link href="/terms" className="footer-legal-link">
              Terms of Service
            </Link>
            <span className="footer-legal-sep" aria-hidden="true">|</span>
            <Link href="/" className="footer-legal-link">
              Sitemap
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
