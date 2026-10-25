"use client";

import { useSyncExternalStore } from "react";

// Guest cart: variant IDs, quantities, and optimistic metadata for instant UI display
export type CartLine = {
  variantId: string;
  quantity: number;
  productName?: string;
  variantLabel?: string;
  imageUrl?: string | null;
  unitPricePaise?: number;
};

const KEY = "svg-cart-v1";
const listeners = new Set<() => void>();
let snapshot: CartLine[] = [];
let loaded = false;

function load() {
  if (loaded || typeof window === "undefined") return;
  loaded = true;
  try {
    const parsed = JSON.parse(localStorage.getItem(KEY) ?? "[]");
    if (Array.isArray(parsed)) {
      snapshot = parsed.filter((l) => typeof l?.variantId === "string" && Number.isInteger(l.quantity) && l.quantity > 0);
    }
  } catch {
    snapshot = [];
  }
}

function save(next: CartLine[]) {
  snapshot = next;
  localStorage.setItem(KEY, JSON.stringify(next));
  listeners.forEach((l) => l());
}

export function addToCart(
  variantId: string,
  quantity = 1,
  metadata?: { productName?: string; variantLabel?: string; imageUrl?: string | null; unitPricePaise?: number }
) {
  load();
  const existing = snapshot.find((l) => l.variantId === variantId);
  save(existing
    ? snapshot.map((l) => (l === existing ? { ...l, ...metadata, quantity: Math.min(l.quantity + quantity, 20) } : l))
    : [...snapshot, { variantId, quantity: Math.min(quantity, 20), ...metadata }]);
}

export function setQuantity(variantId: string, quantity: number) {
  load();
  save(quantity <= 0 ? snapshot.filter((l) => l.variantId !== variantId)
    : snapshot.map((l) => (l.variantId === variantId ? { ...l, quantity: Math.min(quantity, 20) } : l)));
}

export function pruneMissing(missingIds: string[]) {
  if (!missingIds || !missingIds.length) return;
  load();
  const set = new Set(missingIds);
  const next = snapshot.filter((l) => !set.has(l.variantId));
  if (next.length !== snapshot.length) {
    save(next);
  }
}

export function clearCart() {
  load();
  save([]);
}

const empty: CartLine[] = [];
export function useCart(): CartLine[] {
  return useSyncExternalStore(
    (cb) => { listeners.add(cb); return () => listeners.delete(cb); },
    () => { load(); return snapshot; },
    () => empty,
  );
}
