"use client";

import Link from "next/link";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { useCart } from "@/lib/cart";

const links = [
  { href: "/shop", label: "Shop" },
  { href: "/combos", label: "Combos & Gifts" },
  { href: "/track", label: "Track Order" },
  { href: "/contact", label: "Bulk Orders" },
  { href: "/contact", label: "Contact" },
];

export function Header() {
  const [menu, setMenu] = useState(false);
  const [search, setSearch] = useState(false);
  const count = useCart().reduce((s, l) => s + l.quantity, 0);

  return (
    <header className="site-header">
      <div className="header-inner">
        <Link className="wordmark" href="/" aria-label="SVG Dry Fruits home">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <motion.img
            whileHover={{ scale: 1.05 }}
            transition={{ type: "spring", stiffness: 300, damping: 20 }}
            src="/assets/svg-dry-fruits-logo.jpg"
            alt=""
          />
        </Link>
        <nav className="desktop-nav" aria-label="Main navigation">
          {links.map((l) => (
            <Link key={l.href + l.label} href={l.href}>
              {l.label}
            </Link>
          ))}
        </nav>
        <div className="header-actions">
          <button
            className="icon-button search-toggle"
            type="button"
            aria-label={search ? "Hide search" : "Show search"}
            onClick={() => {
              setSearch(!search);
              setMenu(false);
            }}
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" /><path d="m16 16 4.5 4.5" /></svg>
          </button>
          <Link className="icon-button account-link" href="/track" aria-label="Track Orders & Account">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </Link>
          <Link className="bag-link" href="/cart" aria-label={`Shopping bag, ${count} items`}>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 8.5h13l1 12h-15l1-12Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" /></svg>
            <AnimatePresence mode="wait">
              <motion.span
                key={count}
                initial={{ scale: 0.6, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.6, opacity: 0 }}
                transition={{ duration: 0.15 }}
                className="bag-count"
              >
                {count}
              </motion.span>
            </AnimatePresence>
          </Link>
          <button
            className="icon-button menu-toggle"
            type="button"
            aria-expanded={menu}
            aria-controls="mobile-nav"
            aria-label={menu ? "Close navigation menu" : "Open navigation menu"}
            onClick={() => {
              setMenu(!menu);
              setSearch(false);
            }}
          >
            <span></span><span></span>
          </button>
        </div>
      </div>

      <AnimatePresence>
        {search && (
          <motion.form
            key="search-panel"
            initial={{ opacity: 0, y: -8, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -8, scale: 0.98 }}
            transition={{ duration: 0.2, ease: "easeOut" }}
            className="search-panel"
            role="search"
            action="/shop"
          >
            <label htmlFor="site-search">Search the collection</label>
            <div className="search-row">
              <input id="site-search" name="q" type="search" placeholder="Try almonds or dates" autoFocus />
              <button type="submit">Search</button>
            </div>
          </motion.form>
        )}
      </AnimatePresence>

      <AnimatePresence>
        {menu && (
          <motion.nav
            key="mobile-nav"
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
            className="mobile-nav"
            id="mobile-nav"
            aria-label="Mobile navigation"
            onClick={() => setMenu(false)}
          >
            {links.map((l) => (
              <Link key={l.href} href={l.href}>
                {l.label}
              </Link>
            ))}
          </motion.nav>
        )}
      </AnimatePresence>
    </header>
  );
}
