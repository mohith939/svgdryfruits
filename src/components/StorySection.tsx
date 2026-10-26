import Link from "next/link";

export function StorySection() {
  return (
    <section className="story-section-v2" id="story" aria-labelledby="story-title">
      <div className="story-content-wrap">
        <div className="story-copy-card">
          {/* Kicker */}
          <p className="story-kicker">
            <span className="story-kicker-line" aria-hidden="true" />
            <span>A NOTE FROM SVG DRY FRUITS</span>
            <span className="story-kicker-line" aria-hidden="true" />
          </p>

          {/* Headline */}
          <h2 id="story-title" className="story-headline">
            For the jar by the kettle.<br />
            For the friend at the door.<br />
            <em className="story-headline-italic">For keeping close.</em>
          </h2>

          {/* Body Paragraph */}
          <p className="story-desc">
            Our dry fruits are more than a snack — they’re a part of life’s simple, meaningful moments. Whether it’s a quiet evening, a quick energy boost, or sharing with someone special, we’re here for every occasion.
          </p>

          {/* Link */}
          <Link href="/shop" className="story-cta-link">
            <span>Explore our harvest</span>
            <span className="story-cta-arrow" aria-hidden="true">↗</span>
          </Link>
        </div>
      </div>
    </section>
  );
}
