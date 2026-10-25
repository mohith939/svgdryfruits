import type { Metadata } from "next";
import { PageShell } from "@/components/PageShell";

export const metadata: Metadata = { title: "Terms & conditions" };

export default function Terms() {
  return (
    <PageShell kicker="Terms" title={<>Terms &amp; <em>conditions.</em></>} draft>
      <h2>Orders and pricing</h2>
      <p>Prices are shown in Indian rupees. The final price, stock and shipping charge are confirmed by our server when you check out.</p>
      <h2>Payment</h2>
      <p>Orders are paid online. Cash on delivery is not offered.</p>
      <h2>Business details and legal terms</h2>
      <p className="content-muted">Registered business name, address, FSSAI and GST details, governing law and liability terms: to be confirmed by the owner and reviewed by a legal professional.</p>
    </PageShell>
  );
}
