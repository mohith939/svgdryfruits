"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";

export function AdminLoginForm() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [keepSignedIn, setKeepSignedIn] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const res = await fetch("/api/admin/auth/login", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({ email: email.trim(), password }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Login failed. Please check credentials.");
        setLoading(false);
      } else {
        window.location.href = "/admin/orders";
      }

    } catch {
      setError("Network error. Please try again.");
      setLoading(false);
    }
  }

  return (
    <div className="login-v3-wrapper">
      <div className="login-v3-container">
        {/* Left Editorial Hero */}
        <div className="login-v3-left">
          <Link href="/" className="login-v3-brand-logo" aria-label="SVG Dry Fruits Homepage">
            <span className="login-v3-logo-title">SVG</span>
            <span className="login-v3-logo-sub">DRY FRUITS</span>
          </Link>

          <span className="login-v3-kicker">REAL INGREDIENTS. BRIGHTER DAYS.</span>
          <h1 className="login-v3-heading">
            Good Food<br />
            Builds Brighter<br />
            Tomorrows.
          </h1>

          <div className="login-v3-rule" />

          {/* 3 Feature Pillars */}
          <div className="login-v3-features">
            <div className="login-v3-feature-row">
              <div className="login-v3-feat-circle">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                  <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
                </svg>
              </div>
              <span className="login-v3-feat-label">Premium Quality</span>
            </div>

            <div className="login-v3-feature-row">
              <div className="login-v3-feat-circle">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M12 2a10 10 0 0 0-7.07 17.07C7.5 21.64 12 22 12 22s4.5-.36 7.07-2.93A10 10 0 0 0 12 2Z" />
                  <path d="M12 6v10" />
                  <path d="m8 10 4-4 4 4" />
                </svg>
              </div>
              <span className="login-v3-feat-label">Sustainably Sourced</span>
            </div>

            <div className="login-v3-feature-row">
              <div className="login-v3-feat-circle">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                  <path d="m3.3 7 8.7 5 8.7-5" />
                  <path d="M12 22V12" />
                </svg>
              </div>
              <span className="login-v3-feat-label">Packed with Care</span>
            </div>
          </div>

          {/* Bottom Left Script */}
          <div className="login-v3-left-script">
            <span className="login-v3-script-text">
              Real Ingredients.<br />
              Brighter Days.
            </span>
          </div>
        </div>

        {/* Right Column: Portal Login Card */}
        <div className="login-v3-card">
          {/* Emblem Badge */}
          <div className="login-v3-card-emblem-wrap">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/login-emblem-badge.png"
              alt="SVG Dry Fruits Emblem"
              className="login-v3-card-emblem"
            />
          </div>

          <div className="login-v3-card-head">
            <span className="login-v3-card-kicker">STOREFRONT ADMINISTRATION</span>
            <h2 className="login-v3-card-title">Product Management Portal</h2>
            <p className="login-v3-card-desc">
              Sign in to manage catalog products, stock counts, pack variants &amp; pricing.
            </p>
          </div>

          {/* Error Notice */}
          {error && (
            <div className="login-v3-error-box" role="alert" style={{ marginBottom: 14 }}>
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
              <span>{error}</span>
            </div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit} className="login-v3-form">
            {/* Email Address */}
            <div className="login-v3-group">
              <label htmlFor="admin-email" className="login-v3-label">
                Email Address
              </label>
              <div className="login-v3-input-box">
                <span className="login-v3-input-icon">
                  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                  </svg>
                </span>
                <input
                  id="admin-email"
                  type="email"
                  required
                  autoComplete="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="svgdryfruits@gmail.com"
                  className="login-v3-input"
                />
              </div>
            </div>

            {/* Password */}
            <div className="login-v3-group">
              <label htmlFor="admin-pass" className="login-v3-label">
                Password
              </label>
              <div className="login-v3-input-box">
                <span className="login-v3-input-icon">
                  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                  </svg>
                </span>
                <input
                  id="admin-pass"
                  type={showPassword ? "text" : "password"}
                  required
                  autoComplete="current-password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="login-v3-input"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="login-v3-eye-btn"
                  aria-label={showPassword ? "Hide password" : "Show password"}
                >
                  {showPassword ? (
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                      <line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                  ) : (
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  )}
                </button>
              </div>
            </div>

            {/* Keep me signed in & Forgot password */}
            <div className="login-v3-options-row">
              <label className="login-v3-checkbox-label">
                <input
                  type="checkbox"
                  checked={keepSignedIn}
                  onChange={(e) => setKeepSignedIn(e.target.checked)}
                  className="login-v3-checkbox"
                />
                <span>Keep me signed in</span>
              </label>

              <button
                type="button"
                onClick={() => alert("Please contact your database administrator to reset credentials.")}
                className="login-v3-forgot-link"
              >
                Forgot password?
              </button>
            </div>

            {/* Submit */}
            <button
              type="submit"
              disabled={loading}
              className="login-v3-submit-btn"
            >
              <span>{loading ? "Verifying..." : "Sign In & Manage Products"}</span>
              <span aria-hidden="true">→</span>
            </button>
          </form>

          {/* Security Footer */}
          <div className="login-v3-security-note">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            <span>256-bit Encrypted Session | Secure Access</span>
          </div>
        </div>
      </div>
    </div>
  );
}
