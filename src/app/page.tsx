import Link from "next/link";
import { Icon } from "@/components/Icon";
import { instagramPosts, instagramProfile } from "@/lib/social";
import { AnimatedHero } from "@/components/AnimatedHero";
import { CollectionSection } from "@/components/CollectionSection";
import { WhyChooseSection } from "@/components/WhyChooseSection";
import { ThreeSizesSection } from "@/components/ThreeSizesSection";
import { StorySection } from "@/components/StorySection";
import { FramerReveal } from "@/components/FramerReveal";
import { HowItWorks } from "@/components/HowItWorks";
import { listCategories, listProducts } from "@/lib/catalog";

export const revalidate = 60;

const marquee = ["Almonds", "Cashews", "Pistachios", "Walnuts", "Dates", "Raisins", "Figs", "Apricots", "Combos"];

const perks = [
  { icon: "scale", title: "Fixed pack sizes", body: "250 g, 500 g or 1 kg" },
  { icon: "user", title: "Guest checkout", body: "No account needed" },
  { icon: "shield", title: "Secure payments", body: "Online via Razorpay" },
  { icon: "truck", title: "Order tracking", body: "Follow every order" },
];

const faqs = [
  { q: "Do I need an account to order?", a: "No. SVG Dry Fruits uses guest checkout, so you can order with just your delivery details." },
  { q: "Which pack sizes are available?", a: "Products are sold in fixed 250 g, 500 g and 1 kg packs. Not every product is offered in every size; each product page shows what is available." },
  { q: "How are shipping charges decided?", a: "Shipping is set by SVG Dry Fruits and shown clearly in your bag and at checkout before you pay." },
  { q: "How can I pay?", a: "Online through Razorpay. Cash on delivery is not offered." },
  { q: "Where can I read the returns policy?", a: "See the Returns page linked in the footer. Questions are welcome through the Contact page." },
];

export default async function Home() {
  const igProfile = instagramProfile();
  const igPosts = instagramPosts();
  const wa = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER?.replace(/\D/g, "");

  const [dbProducts, dbCategories] = await Promise.all([
    listProducts().catch(() => []),
    listCategories().catch(() => []),
  ]);

  const formattedProducts = dbProducts.map((p) => ({
    id: p.id,
    slug: p.slug,
    name: p.name,
    imageUrl: p.imageUrl,
    categorySlug: p.category?.slug,
    variants: p.variants.map((v) => ({
      id: v.id,
      label: v.label,
      pricePaise: v.pricePaise,
      comparePricePaise: v.comparePricePaise,
    })),
  }));

  const formattedCategories = dbCategories.map((c) => ({
    id: c.id,
    slug: c.slug,
    name: c.name,
    imageUrl: c.imageUrl,
  }));

  return (
    <>
      <AnimatedHero />
      <CollectionSection initialProducts={formattedProducts} initialCategories={formattedCategories} />
      <WhyChooseSection />

      <div className="marquee" aria-hidden="true">
        <div className="marquee-track">
          {[...marquee, ...marquee].map((m, i) => <span key={i}>{m}<i>✦</i></span>)}
        </div>
      </div>

      <ThreeSizesSection />

      <FramerReveal>
        <section className="gifting-section" id="gifting" aria-labelledby="gifting-title">
          <div className="gifting-photo">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src="/assets/dates-reference.jpg" alt="Dates arranged in a small glass dish" loading="lazy" />
            <span>FOR THE HOST, THE VISIT, THE LITTLE OCCASION</span>
          </div>
          <div className="gifting-copy">
            <p className="section-kicker">A THOUGHT TO TAKE ALONG</p>
            <h2 id="gifting-title">A small gesture.<br /><em>A good one.</em></h2>
            <p>A gift can be simple and still feel considered. Browse boxes and combinations that are a pleasure to give, for festivals, thank-yous and everything in between.</p>
            <Link className="underlined-link" href="/combos">Explore gifting <span aria-hidden="true">↗</span></Link>
          </div>
        </section>
      </FramerReveal>

      <HowItWorks />

      <StorySection />

      <section className="faq-section" aria-labelledby="faq-title">
        <div className="faq-head">
          <p className="section-kicker">GOOD TO KNOW</p>
          <h2 id="faq-title">Questions,<br /><em>answered.</em></h2>
        </div>
        <div className="faq-list">
          {faqs.map((f) => (
            <details key={f.q}>
              <summary>{f.q}</summary>
              <p>{f.a}</p>
            </details>
          ))}
        </div>
      </section>

      {igProfile && (
        <section className="insta-section" aria-labelledby="insta-title">
          <div className="insta-head">
            <p className="section-kicker">FOLLOW ALONG</p>
            <h2 id="insta-title">See what’s <em>on the shelf.</em></h2>
            <p>New arrivals, gift ideas and little moments from SVG Dry Fruits, on Instagram.</p>
            <a className="button button-primary" href={igProfile} target="_blank" rel="noopener noreferrer"><Icon name="instagram" size={20} /> Follow on Instagram</a>
          </div>
          {igPosts.length > 0 && (
            <ul className="insta-grid">
              {igPosts.map((u, i) => (
                <li key={u}>
                  <a href={u} target="_blank" rel="noopener noreferrer" aria-label={`Open Instagram post ${i + 1}`}>
                    <Icon name="instagram" size={34} />
                    <span>Post {String(i + 1).padStart(2, "0")}</span>
                    <Icon className="insta-arrow" name="arrow" size={20} />
                  </a>
                </li>
              ))}
            </ul>
          )}
        </section>
      )}

      <section className="cta-band" aria-labelledby="cta-title">
        <h2 id="cta-title">Something you’d like <em>to know?</em></h2>
        <div className="cta-actions">
          <Link className="button button-light" href="/contact">Contact us <span aria-hidden="true">↗</span></Link>
          {wa && <a className="button button-outline" href={`https://wa.me/${wa}`}><Icon name="whatsapp" size={20} /> WhatsApp</a>}
        </div>
      </section>
    </>
  );
}
