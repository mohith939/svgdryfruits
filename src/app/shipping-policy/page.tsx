import type { Metadata } from "next";
import { PageShell } from "@/components/PageShell";

export const metadata: Metadata = { title: "Shipping policy" };

export default function Shipping() {
  return (
    <PageShell kicker="Shipping policy" title={<>Shipping <em>policy.</em></>} draft>
      <h2>Shipping charges</h2>
      <p>Shipping charges are set by SVG Dry Fruits and shown in your bag and at checkout before you pay. The charge shown when you place your order is the charge you pay.</p>
      <h2>Delivery areas and timelines</h2>
      <p className="content-muted">Serviceable areas, dispatch time and estimated delivery time: to be confirmed by the owner.</p>
      <h2>Tracking</h2>
      <p>After you order, you can follow its progress from the Track order page using your order number.</p>
      <h2>Questions</h2>
      <p>For anything about your delivery, please use the Contact page.</p>
    </PageShell>
  );
}
