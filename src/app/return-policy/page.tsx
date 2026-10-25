import type { Metadata } from "next";
import { PageShell } from "@/components/PageShell";

export const metadata: Metadata = { title: "Returns" };

export default function Returns() {
  return (
    <PageShell kicker="Returns" title={<>Returns &amp; <em>refunds.</em></>} draft>
      <h2>If something isn’t right</h2>
      <p>If your order arrives damaged or incorrect, please contact us as soon as possible with your order number so we can help.</p>
      <h2>Return eligibility and time limits</h2>
      <p className="content-muted">Eligibility rules, time limits and conditions for food items: to be confirmed by the owner.</p>
      <h2>Refunds</h2>
      <p>Approved refunds are recorded against your order. The refund method and timing: to be confirmed by the owner.</p>
    </PageShell>
  );
}
