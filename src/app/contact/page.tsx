import Link from "next/link";
import type { Metadata } from "next";
import { Icon } from "@/components/Icon";
import { instagramProfile } from "@/lib/social";
import { ContactForm } from "@/components/ContactForm";

export const metadata: Metadata = { title: "Contact" };

export default function Contact() {
  const email = process.env.NEXT_PUBLIC_CONTACT_EMAIL;
  const wa = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER?.replace(/\D/g, "");
  const ig = instagramProfile();
  const hours = process.env.NEXT_PUBLIC_BUSINESS_HOURS;
  const address = process.env.NEXT_PUBLIC_BUSINESS_ADDRESS;

  const channels = [
    wa && { icon: "whatsapp", title: "WhatsApp", body: "The quickest way to ask about an order or a product.", label: "Message us", href: `https://wa.me/${wa}` },
    email && { icon: "mail", title: "Email", body: email, label: "Write to us", href: `mailto:${email}` },
    ig && { icon: "instagram", title: "Instagram", body: "New arrivals and gift ideas.", label: "Follow us", href: ig, external: true },
  ].filter(Boolean) as { icon: string; title: string; body: string; label: string; href: string; external?: boolean }[];

  const help = [
    { icon: "truck", title: "Track an order", body: "Follow your order with its order number.", href: "/track" },
    { icon: "clock", title: "Shipping policy", body: "How and when orders are sent.", href: "/shipping-policy" },
    { icon: "leaf", title: "Returns", body: "What to do if something isn’t right.", href: "/return-policy" },
    { icon: "bag", title: "Your bag", body: "Review items before checkout.", href: "/cart" },
  ];

  return (
    <>
      <section className="shop-intro" aria-labelledby="contact-title">
        <div className="breadcrumbs"><Link href="/">Home</Link><span aria-hidden="true">/</span><span>Contact</span></div>
        <div className="shop-intro-row">
          <h1 id="contact-title">Get in <em>touch.</em></h1>
          <p>Questions about a product, a pack size or an order? We’re happy to help.</p>
        </div>
      </section>

      <section className="contact-section" aria-label="Contact channels">
        {channels.length > 0 ? (
          <ul className="contact-grid">
            {channels.map((c) => (
              <li key={c.title} className="contact-card">
                <Icon className="contact-icon" name={c.icon} size={36} />
                <h2>{c.title}</h2>
                <p>{c.body}</p>
                <a className="underlined-link" href={c.href} {...(c.external ? { target: "_blank", rel: "noopener noreferrer" } : {})}>{c.label} <span aria-hidden="true">↗</span></a>
              </li>
            ))}
          </ul>
        ) : (
          <div className="contact-card contact-pending">
            <Icon className="contact-icon" name="chat" size={36} />
            <h2>Contact details coming soon</h2>
            <p>Business contact details will be added here once confirmed by the owner.</p>
          </div>
        )}

        {(hours || address) && (
          <ul className="contact-meta">
            {hours && <li><Icon name="clock" size={22} /><span><strong>Hours</strong>{hours}</span></li>}
            {address && <li><Icon name="pin" size={22} /><span><strong>Address</strong>{address}</span></li>}
          </ul>
        )}

        <ContactForm />
      </section>

      <section className="contact-section" aria-labelledby="help-title">
        <h2 id="help-title" className="contact-subtitle">Looking for something <em>specific?</em></h2>
        <ul className="contact-help">
          {help.map((h) => (
            <li key={h.href}>
              <Link href={h.href}>
                <Icon name={h.icon} size={28} />
                <strong>{h.title}</strong>
                <span>{h.body}</span>
                <Icon className="insta-arrow" name="arrow" size={18} />
              </Link>
            </li>
          ))}
        </ul>
      </section>
    </>
  );
}
