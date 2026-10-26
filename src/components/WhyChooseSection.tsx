import Link from "next/link";
import { Icon } from "./Icon";

interface Pillar {
  icon: string;
  title: string;
  desc: string;
}

const PILLARS: Pillar[] = [
  {
    icon: "leaf",
    title: "Premium Quality",
    desc: "Carefully selected for the best taste and freshness.",
  },
  {
    icon: "globe",
    title: "Ethically Sourced",
    desc: "From trusted farms across the globe.",
  },
  {
    icon: "package-sealed",
    title: "Hygienically Packed",
    desc: "Sealed with care to retain natural goodness.",
  },
  {
    icon: "truck",
    title: "Fast & Reliable Delivery",
    desc: "Your favourite dry fruits delivered to your doorstep.",
  },
];

export function WhyChooseSection() {
  return (
    <section className="why-choose-section" aria-labelledby="why-choose-title">
      {/* Main Content Layout */}
      <div className="why-choose-container">
        {/* Left Side: Copy & CTA */}
        <div className="why-choose-intro">
          <p className="why-choose-kicker">— WHY CHOOSE SVG DRY FRUITS —</p>
          <h2 id="why-choose-title" className="why-choose-headline">
            PURE GOODNESS<br />IN EVERY BITE.
          </h2>
          <p className="why-choose-desc">
            We go beyond just dry fruits — we bring you a promise of quality, care and a healthier tomorrow.
          </p>
          <Link href="/shop" className="why-choose-cta">
            EXPLORE COLLECTION <span aria-hidden="true">→</span>
          </Link>
        </div>

        {/* Right Side: 4 Feature Pillars with vertical hairline dividers */}
        <div className="why-choose-pillars" role="list">
          {PILLARS.map((p, idx) => (
            <div key={idx} className="why-choose-pillar-item" role="listitem">
              <div className="why-choose-icon-badge">
                <Icon name={p.icon} size={28} />
              </div>
              <h3 className="why-choose-pillar-title">{p.title}</h3>
              <p className="why-choose-pillar-desc">{p.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
