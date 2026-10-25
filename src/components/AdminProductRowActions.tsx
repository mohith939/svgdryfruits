"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

interface AdminProductRowActionsProps {
  productId: string;
  productName: string;
  slug: string;
}

export function AdminProductRowActions({
  productId,
  productName,
  slug,
}: AdminProductRowActionsProps) {
  const router = useRouter();
  const [isDeleting, setIsDeleting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleDelete = async () => {
    const confirmed = window.confirm(
      `Are you sure you want to delete "${productName}"?\nIf this product is linked to past orders, it will be safely archived instead.`
    );
    if (!confirmed) return;

    setIsDeleting(true);
    setError(null);

    try {
      const res = await fetch(`/api/admin/products/${productId}`, {
        method: "DELETE",
      });
      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.error || "Failed to delete product");
      }
      router.refresh();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : "An error occurred";
      setError(msg);
      alert(msg);
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="admin-v3-actions-row">
      <Link
        href={`/products/${slug}`}
        target="_blank"
        className="admin-v3-btn-view"
        title="View on Storefront"
      >
        <span>View</span>
        <span aria-hidden="true">↗</span>
      </Link>
      <Link
        href={`/admin/products/${productId}`}
        className="admin-v3-btn-edit"
      >
        Edit
      </Link>
      <button
        type="button"
        onClick={handleDelete}
        disabled={isDeleting}
        className="admin-v3-btn-delete"
      >
        {isDeleting ? "..." : "Delete"}
      </button>
    </div>
  );
}
