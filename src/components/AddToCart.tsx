"use client";

import { useState } from "react";
import Link from "next/link";
import { addToCart } from "@/lib/cart";
import { formatINR } from "@/lib/money";
import { triggerCartToast } from "./CartToast";

type V = { id: string; label: string; pricePaise: number; comparePricePaise: number | null; stock: number; lowStockThreshold: number };

export function AddToCart({ variants, productName }: { variants: V[]; productName?: string }) {
  const [id, setId] = useState((variants.find((v) => v.stock > 0) ?? variants[0])?.id);
  const [qty, setQty] = useState(1);
  const [added, setAdded] = useState(false);
  const v = variants.find((x) => x.id === id);
  if (!v) return <p className="pdp-unavailable">This product is currently unavailable.</p>;

  const max = Math.min(20, v.stock);
  const soldOut = v.stock < 1;
  const clamp = (n: number) => Math.max(1, Math.min(max || 1, n));
  const total = v.pricePaise * qty;

  return (
    <div className="pdp-buy">
      <div className="pdp-price-row">
        <span className="pdp-price">{formatINR(v.pricePaise)}</span>
        {v.comparePricePaise && v.comparePricePaise > v.pricePaise && (
          <s className="pdp-compare">{formatINR(v.comparePricePaise)}</s>
        )}
        <span className={`pdp-stock${soldOut ? " is-out" : v.stock <= v.lowStockThreshold ? " is-low" : ""}`}>
          {soldOut ? "Sold out" : v.stock <= v.lowStockThreshold ? `Only ${v.stock} left` : "In stock"}
        </span>
      </div>

      <fieldset className="pdp-field">
        <legend>Pack size</legend>
        <div className="pdp-sizes" role="radiogroup">
          {variants.map((x) => (
            <button key={x.id} type="button" role="radio" aria-checked={x.id === id}
              className={`pdp-size${x.id === id ? " is-active" : ""}`} disabled={x.stock < 1}
              onClick={() => { setId(x.id); setQty(1); setAdded(false); }}>
              <span>{x.label}</span>
              <small>{x.stock < 1 ? "Sold out" : formatINR(x.pricePaise)}</small>
            </button>
          ))}
        </div>
      </fieldset>

      <div className="pdp-actions">
        <div className="pdp-qty" aria-label="Quantity">
          <button type="button" aria-label="Decrease quantity" disabled={soldOut || qty <= 1} onClick={() => setQty(clamp(qty - 1))}>−</button>
          <output aria-live="polite">{qty}</output>
          <button type="button" aria-label="Increase quantity" disabled={soldOut || qty >= max} onClick={() => setQty(clamp(qty + 1))}>+</button>
        </div>
        <button className="button button-primary pdp-add" type="button" disabled={soldOut}
          onClick={() => {
            addToCart(v.id, qty);
            setAdded(true);
            triggerCartToast({
              title: productName ?? "Dry Fruits Pack",
              size: v.label,
              qty,
            });
          }}>
          {soldOut ? "Sold out" : `Add to bag · ${formatINR(total)}`}
        </button>
      </div>
      {qty >= max && !soldOut && <p className="pdp-hint">Maximum available quantity selected.</p>}
      <p className="pdp-added" role="status">
        {added && <>Added to your bag. <Link href="/cart">View bag →</Link></>}
      </p>
    </div>
  );
}
