"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";

interface AdminTopNavProps {
  adminEmail: string;
}

export function AdminTopNav({ adminEmail }: AdminTopNavProps) {
  const pathname = usePathname();
  const [showProfileMenu, setShowProfileMenu] = useState(false);

  const navItems = [
    {
      label: "Overview",
      href: "/admin",
      exact: true,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
          <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
      ),
    },
    {
      label: "Products & Stock",
      href: "/admin/products",
      exact: false,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
          <path d="m3.3 7 8.7 5 8.7-5" />
          <path d="M12 22V12" />
        </svg>
      ),
    },
    {
      label: "Categories",
      href: "/admin/categories",
      exact: false,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <polygon points="12 2 2 7 12 12 22 7 12 2" />
          <polyline points="2 17 12 22 22 17" />
          <polyline points="2 12 12 17 22 12" />
        </svg>
      ),
    },
    {
      label: "Combos",
      href: "/admin/combos",
      exact: false,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
          <line x1="7" y1="7" x2="7.01" y2="7" />
        </svg>
      ),
    },
    {
      label: "Orders",
      href: "/admin/orders",
      exact: false,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
          <polyline points="14 2 14 8 20 8" />
          <line x1="16" y1="13" x2="8" y2="13" />
          <line x1="16" y1="17" x2="8" y2="17" />
          <polyline points="10 9 9 9 8 9" />
        </svg>
      ),
    },
    {
      label: "Coupons",
      href: "/admin/coupons",
      exact: false,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <line x1="19" y1="5" x2="5" y2="19" />
          <circle cx="6.5" cy="6.5" r="2.5" />
          <circle cx="17.5" cy="17.5" r="2.5" />
        </svg>
      ),
    },
    {
      label: "Settings",
      href: "/admin/settings",
      exact: false,
      icon: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <circle cx="12" cy="12" r="3" />
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
        </svg>
      ),
    },
  ];

  function isActive(item: typeof navItems[0]) {
    if (item.exact) {
      return pathname === item.href;
    }
    return pathname === item.href || pathname.startsWith(item.href + "/");
  }

  const initial = adminEmail.charAt(0).toUpperCase() || "M";

  return (
    <header className="admin-v4-topbar">
      <div className="admin-v4-topbar-left">
        {/* Brand Logo */}
        <Link href="/admin" className="admin-v4-brand" aria-label="SVG Admin Dashboard">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src="/assets/login-emblem-badge.png"
            alt=""
            className="admin-v4-brand-emblem"
          />
          <div className="admin-v4-brand-text">
            <span className="admin-v4-brand-title">SVG</span>
            <span className="admin-v4-brand-sub">DRY FRUITS</span>
          </div>
        </Link>

        {/* Navigation Items with Exact Line Icons */}
        <nav className="admin-v4-nav" aria-label="Admin Navigation">
          {navItems.map((item) => {
            const active = isActive(item);
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`admin-v4-nav-item ${active ? "admin-v4-nav-active" : ""}`}
              >
                <span className="admin-v4-nav-icon">{item.icon}</span>
                <span>{item.label}</span>
              </Link>
            );
          })}
        </nav>
      </div>

      <div className="admin-v4-topbar-right">
        {/* View Store */}
        <Link href="/" target="_blank" className="admin-v4-view-store">
          <span>View Store</span>
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <line x1="7" y1="17" x2="17" y2="7" />
            <polyline points="7 7 17 7 17 17" />
          </svg>
        </Link>

        <div className="admin-v4-divider" />

        {/* Email */}
        <span className="admin-v4-email">{adminEmail}</span>

        {/* User Profile */}
        <div className="admin-v4-profile-wrap">
          <button
            type="button"
            onClick={() => setShowProfileMenu(!showProfileMenu)}
            className="admin-v4-profile-btn"
            aria-expanded={showProfileMenu}
            aria-label="User Profile Menu"
          >
            <div className="admin-v4-avatar">{initial}</div>
            <div className="admin-v4-user-info">
              <span className="admin-v4-user-name">Mohith</span>
              <span className="admin-v4-user-role">
                Admin
                <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{ marginLeft: 3 }}>
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </span>
            </div>
          </button>

          {showProfileMenu && (
            <div className="admin-v4-dropdown">
              <div className="admin-v4-dropdown-header">
                <span className="admin-v4-dropdown-name">Mohith Admin</span>
                <span className="admin-v4-dropdown-email">{adminEmail}</span>
              </div>
              <div className="admin-v4-dropdown-divider" />
              <Link href="/admin/settings" className="admin-v4-dropdown-item" onClick={() => setShowProfileMenu(false)}>
                Store Settings
              </Link>
              <form
                action="/api/admin/auth/logout"
                method="POST"
                onSubmit={async (e) => {
                  e.preventDefault();
                  try {
                    await fetch("/api/admin/auth/logout", { method: "POST" });
                  } catch {}
                  window.location.href = "/admin/login";
                }}
              >
                <button type="submit" className="admin-v4-dropdown-logout">
                  Sign out
                </button>
              </form>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
