import type { Metadata } from "next";
import { PageShell } from "@/components/PageShell";

export const metadata: Metadata = { title: "Privacy policy" };

export default function Privacy() {
  return (
    <PageShell kicker="Privacy policy" title={<>Privacy <em>policy.</em></>} draft>
      <h2>What we collect</h2>
      <p>To fulfil an order we collect your name, phone number, delivery address and, optionally, your email address. Payment details are handled by Razorpay and are not stored by this website.</p>
      <h2>How it’s used</h2>
      <p>Your details are used to process, deliver and support your order.</p>
      <h2>Retention, sharing and your rights</h2>
      <p className="content-muted">Retention periods, third parties, and how to request changes or deletion: to be confirmed by the owner and reviewed by a legal professional.</p>
    </PageShell>
  );
}
