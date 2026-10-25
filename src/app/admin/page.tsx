import Link from "next/link";
import { redirect } from "next/navigation";
import { desc, eq, inArray, sql } from "drizzle-orm";
import { getDb } from "@/db/client";
import { orders, variants, products } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";
import { formatINR } from "@/lib/money";
import { Icon } from "@/components/Icon";

export const dynamic = "force-dynamic";

export default async function AdminOverviewPage() {
  const session = await getAdminSession();
  if (!session) redirect("/admin/login");

  const db = await getDb();

  // Metrics:
  // 1. Total orders count
  const [{ totalOrdersCount }] = await db
    .select({ totalOrdersCount: sql<number>`count(*)::int` })
    .from(orders);

  // 2. Verified revenue (paid orders only)
  const [{ verifiedRevenuePaise }] = await db
    .select({ verifiedRevenuePaise: sql<number>`coalesce(sum(${orders.totalPaise}), 0)::int` })
    .from(orders)
    .where(eq(orders.paymentStatus, "paid"));

  // 3. Orders awaiting fulfillment
  const [{ awaitingActionCount }] = await db
    .select({ awaitingActionCount: sql<number>`count(*)::int` })
    .from(orders)
    .where(sql`${orders.paymentStatus} = 'paid' AND ${orders.fulfillmentStatus} IN ('confirmed', 'processing', 'packed')`);

  // 4. Low stock variants
  const lowStockRows = await db
    .select({
      variantId: variants.id,
      sku: variants.sku,
      label: variants.label,
      stock: variants.stock,
      threshold: variants.lowStockThreshold,
      productName: products.name,
      productSlug: products.slug,
    })
    .from(variants)
    .innerJoin(products, eq(products.id, variants.productId))
    .where(sql`${variants.active} = true AND ${variants.stock} <= ${variants.lowStockThreshold}`)
    .limit(10);

  // 5. Recent orders
  const recentOrders = await db
    .select()
    .from(orders)
    .orderBy(desc(orders.createdAt))
    .limit(8);

  return (
    <div className="admin-content">
      <div className="admin-page-header">
        <div>
          <h1>Dashboard <em>Overview</em></h1>
          <p>Real-time metrics and order activity for SVG Dry Fruits.</p>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="admin-kpi-grid">
        <div className="admin-kpi-card">
          <div className="kpi-icon-wrap"><Icon name="card" size={24} /></div>
          <div>
            <span className="kpi-label">Verified Revenue</span>
            <strong className="kpi-value">{formatINR(verifiedRevenuePaise)}</strong>
            <span className="kpi-hint">Paid orders only</span>
          </div>
        </div>

        <div className="admin-kpi-card">
          <div className="kpi-icon-wrap"><Icon name="bag" size={24} /></div>
          <div>
            <span className="kpi-label">Total Orders</span>
            <strong className="kpi-value">{totalOrdersCount}</strong>
            <span className="kpi-hint">All historical checkouts</span>
          </div>
        </div>

        <div className="admin-kpi-card">
          <div className="kpi-icon-wrap"><Icon name="truck" size={24} /></div>
          <div>
            <span className="kpi-label">Awaiting Fulfillment</span>
            <strong className="kpi-value">{awaitingActionCount}</strong>
            <span className="kpi-hint">Confirmed &amp; packing</span>
          </div>
        </div>

        <div className="admin-kpi-card">
          <div className="kpi-icon-wrap"><Icon name="scale" size={24} /></div>
          <div>
            <span className="kpi-label">Low Stock Alerts</span>
            <strong className="kpi-value">{lowStockRows.length}</strong>
            <span className="kpi-hint">Variants at or below threshold</span>
          </div>
        </div>
      </div>

      <div className="admin-two-cols">
        {/* Recent Orders Table */}
        <div className="admin-card">
          <div className="admin-card-head">
            <h2>Recent Orders</h2>
            <Link href="/admin/orders" className="underlined-link">View all orders →</Link>
          </div>

          {recentOrders.length === 0 ? (
            <p className="admin-empty-text">No orders placed yet. Orders will appear here in real time.</p>
          ) : (
            <div className="admin-table-wrap">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Fulfillment</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {recentOrders.map((o) => (
                    <tr key={o.id}>
                      <td><strong>{o.orderNumber}</strong></td>
                      <td>{o.customerName}</td>
                      <td>{formatINR(o.totalPaise)}</td>
                      <td>
                        <span className={`badge ${o.paymentStatus === "paid" ? "badge-success" : o.paymentStatus === "pending" ? "badge-warning" : "badge-danger"}`}>
                          {o.paymentStatus}
                        </span>
                      </td>
                      <td>
                        <span className="badge badge-neutral">{o.fulfillmentStatus.replace("_", " ")}</span>
                      </td>
                      <td>
                        <Link href={`/admin/orders/${o.id}`} className="admin-action-link">
                          Manage →
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Low Stock Warning Box */}
        <div className="admin-card">
          <div className="admin-card-head">
            <h2>Low Stock Alerts</h2>
            <Link href="/admin/products" className="underlined-link">Manage inventory →</Link>
          </div>

          {lowStockRows.length === 0 ? (
            <p className="admin-empty-text">All active variants are above their low-stock thresholds.</p>
          ) : (
            <ul className="admin-alert-list">
              {lowStockRows.map((r) => (
                <li key={r.variantId} className="admin-alert-item">
                  <div>
                    <strong>{r.productName} ({r.label})</strong>
                    <span>SKU: {r.sku}</span>
                  </div>
                  <div className="admin-stock-badge">
                    <strong>{r.stock} left</strong>
                    <span>Threshold: {r.threshold}</span>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}
