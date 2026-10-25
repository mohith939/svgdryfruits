import type { Metadata } from "next";
import { PageShell } from "@/components/PageShell";

export const metadata: Metadata = { title: "About" };

export default function About() {
  return (
    <PageShell kicker="About" title={<>A little <em>about us.</em></>}>
      <h2>SVG Dry Fruits</h2>
      <p>SVG Dry Fruits is a dry-fruit brand built around a simple idea: good things, kept close. We want ordering to feel as easy as opening the pantry.</p>
      <h2>How we shop with you</h2>
      <ul>
        <li>Fixed pack sizes of 250 g, 500 g and 1 kg, so you always know what you’re getting.</li>
        <li>Clear prices, with shipping shown before you pay.</li>
        <li>Guest checkout. No account is needed.</li>
        <li>Online payment through Razorpay, and order tracking after you buy.</li>
      </ul>
      <p className="content-muted">Sourcing, certifications and company details will be added here once confirmed by the owner.</p>
    </PageShell>
  );
}
