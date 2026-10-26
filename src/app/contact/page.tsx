import Link from "next/link";
import type { Metadata } from "next";
import { Icon } from "@/components/Icon";
import { ContactForm } from "@/components/ContactForm";

export const metadata: Metadata = {
  title: "Contact Us | SVG Dry Fruits",
  description:
    "Get in touch with SVG Dry Fruits for order inquiries, pack sizes, bulk corporate orders, or assistance.",
};

export default function Contact() {
  const email = process.env.NEXT_PUBLIC_CONTACT_EMAIL || "orders@svgdryfruits.com";
  const rawWa = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER?.replace(/\D/g, "") || "918340956469";
  const displayWa = "+91 83409 56469";

  return (
    <div className="min-contact-page">
      {/* Breadcrumbs */}
      <nav className="min-contact-breadcrumbs" aria-label="Breadcrumb">
        <Link href="/">Home</Link>
        <span aria-hidden="true">/</span>
        <span style={{ color: "var(--pine)", fontWeight: 500 }}>Contact</span>
      </nav>

      {/* Hero Header */}
      <header className="min-contact-hero">
        <p className="min-contact-kicker">GET IN TOUCH</p>
        <h1 className="min-contact-title">
          We’re here to <em>help.</em>
        </h1>
        <p className="min-contact-desc">
          Have questions about our harvest, pack sizes, bulk corporate gifting, or an existing delivery?
          Reach out through any of the channels below or leave us a message.
        </p>
      </header>

      {/* 2-Column Minimalist Layout */}
      <div className="min-contact-grid">
        {/* Left Column: Direct Touchpoints & Services */}
        <div className="min-contact-cards">
          {/* Card 1: WhatsApp Support */}
          <div className="min-contact-card">
            <div className="min-contact-card-top">
              <span className="min-contact-card-label">
                <Icon name="whatsapp" size={18} />
                WhatsApp Support
              </span>
              <span className="min-contact-card-badge green">● Active</span>
            </div>
            <p className="min-contact-card-val">{displayWa}</p>
            <p className="min-contact-card-sub">
              Fastest way to get real-time answers for active orders, delivery updates, and tracking.
            </p>
            <a
              href={`https://wa.me/${rawWa}?text=Hi%20SVG%20Dry%20Fruits,%20I%20have%20an%20enquiry.`}
              target="_blank"
              rel="noopener noreferrer"
              className="min-contact-card-link"
            >
              <span>Chat on WhatsApp</span>
              <span aria-hidden="true">↗</span>
            </a>
          </div>

          {/* Card 2: Email Inquiries */}
          <div className="min-contact-card">
            <div className="min-contact-card-top">
              <span className="min-contact-card-label">
                <Icon name="mail" size={18} />
                Email Inquiries
              </span>
              <span className="min-contact-card-badge gold">Direct</span>
            </div>
            <p className="min-contact-card-val">{email}</p>
            <p className="min-contact-card-sub">
              For bulk and corporate quotations, feedback, invoice copies, or general questions.
            </p>
            <a
              href={`mailto:${email}?subject=SVG%20Dry%20Fruits%20Enquiry`}
              className="min-contact-card-link"
            >
              <span>Send an email</span>
              <span aria-hidden="true">↗</span>
            </a>
          </div>

          {/* Card 3: Dispatch Center */}
          <div className="min-contact-card">
            <div className="min-contact-card-top">
              <span className="min-contact-card-label">
                <Icon name="pin" size={18} />
                Dispatch Center
              </span>
              <span className="min-contact-card-badge gold">Bengaluru</span>
            </div>
            <p className="min-contact-card-val" style={{ fontSize: 16 }}>
              SVG Dry Fruits Studio &amp; Packing Center
            </p>
            <p className="min-contact-card-sub" style={{ marginBottom: 0 }}>
              Bengaluru, Karnataka, India — 560038<br />
              Mon – Sat: 9:00 AM – 7:00 PM IST
            </p>
          </div>

          {/* Quick Helpful Links */}
          <div className="min-contact-quick-links">
            <p className="min-contact-quick-links-title">Quick Self-Service</p>
            <ul className="min-contact-quick-links-list">
              <li>
                <Link href="/track">
                  <span>Track an existing shipment</span>
                  <span aria-hidden="true">→</span>
                </Link>
              </li>
              <li>
                <Link href="/shipping-policy">
                  <span>Shipping timelines &amp; delivery info</span>
                  <span aria-hidden="true">→</span>
                </Link>
              </li>
              <li>
                <Link href="/return-policy">
                  <span>Return &amp; refund guidelines</span>
                  <span aria-hidden="true">→</span>
                </Link>
              </li>
            </ul>
          </div>
        </div>

        {/* Right Column: Sleek Form */}
        <div>
          <ContactForm />
        </div>
      </div>
    </div>
  );
}
