"use client";

import { useRef, useState } from "react";

interface MultiImageUploaderProps {
  images: string[];
  onChange: (images: string[]) => void;
  label?: string;
}

const DEFAULT_PRESETS = [
  { label: "Pistachios HD", url: "/assets/shop-pistachios-hd.jpg" },
  { label: "California Almonds", url: "/assets/pdp-hero-almonds.jpg" },
  { label: "Royal Cashews", url: "/assets/shop-cashews.jpg" },
  { label: "Kashmiri Walnuts", url: "/assets/shop-walnuts.jpg" },
  { label: "Medjool Dates", url: "/assets/shop-premium-dates.jpg" },
  { label: "Afghan Anjeer", url: "/assets/shop-dried-figs.jpg" },
  { label: "Golden Raisins", url: "/assets/shop-golden-raisins.jpg" },
  { label: "Turkish Apricots", url: "/assets/shop-dried-apricots.jpg" },
  { label: "Pumpkin Seeds", url: "/assets/shop-pumpkin-seeds.jpg" },
  { label: "Sunflower Seeds", url: "/assets/shop-sunflower-seeds.jpg" },
  { label: "Healthy Trail Mix", url: "/assets/shop-mixed-nuts.jpg" },
  { label: "Luxury Gift Box", url: "/assets/mixed-fruit-reference.jpg" },
];

export function MultiImageUploader({
  images,
  onChange,
  label = "Product Images & Gallery",
}: MultiImageUploaderProps) {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleUploadFiles = async (files: FileList | File[]) => {
    if (!files || files.length === 0) return;
    setUploading(true);
    setError(null);

    const newUrls: string[] = [];
    try {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append("file", file);

        const res = await fetch("/api/admin/upload", {
          method: "POST",
          body: formData,
        });

        const data = await res.json();
        if (!res.ok) {
          throw new Error(data.error || `Failed to upload ${file.name}`);
        }
        if (data.url) {
          newUrls.push(data.url);
        }
      }

      if (newUrls.length > 0) {
        onChange([...images, ...newUrls]);
      }
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : "Upload error";
      setError(msg);
    } finally {
      setUploading(false);
    }
  };

  const removeImage = (index: number) => {
    const updated = images.filter((_, i) => i !== index);
    onChange(updated);
  };

  const makeCover = (index: number) => {
    if (index === 0) return;
    const target = images[index];
    const rest = images.filter((_, i) => i !== index);
    onChange([target, ...rest]);
  };

  const addPreset = (url: string) => {
    if (!images.includes(url)) {
      onChange([...images, url]);
    }
  };

  return (
    <div className="multi-image-uploader" style={{ marginTop: 12 }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 8 }}>
        <label style={{ fontWeight: 600, fontSize: 13, color: "#222" }}>
          {label} ({images.length} added)
        </label>
        <span style={{ fontSize: 12, color: "#777" }}>
          First image is used as the primary storefront cover
        </span>
      </div>

      {error && (
        <div style={{ padding: "8px 12px", background: "#fde8e8", color: "#9b1c1c", borderRadius: 6, fontSize: 12, marginBottom: 10 }}>
          {error}
        </div>
      )}

      {/* Gallery Grid */}
      <div
        style={{
          display: "grid",
          gridTemplateColumns: "repeat(auto-fill, minmax(130px, 1fr))",
          gap: 12,
          marginBottom: 14,
        }}
      >
        {images.map((url, idx) => (
          <div
            key={`${url}-${idx}`}
            style={{
              position: "relative",
              aspectRatio: "1",
              borderRadius: 8,
              border: idx === 0 ? "2px solid #C5A059" : "1px solid #D8D0C5",
              overflow: "hidden",
              background: "#fff",
              boxShadow: idx === 0 ? "0 4px 12px rgba(197, 160, 89, 0.25)" : "none",
            }}
          >
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={url}
              alt={`Product preview ${idx + 1}`}
              style={{ width: "100%", height: "100%", objectFit: "cover" }}
            />

            {/* Badge for cover */}
            <span
              style={{
                position: "absolute",
                top: 4,
                left: 4,
                background: idx === 0 ? "#073D26" : "rgba(0,0,0,0.6)",
                color: idx === 0 ? "#E2C376" : "#fff",
                fontSize: 10,
                fontWeight: 700,
                padding: "2px 6px",
                borderRadius: 4,
                letterSpacing: 0.3,
              }}
            >
              {idx === 0 ? "★ MAIN COVER" : `#${idx + 1}`}
            </span>

            {/* Actions overlay */}
            <div
              style={{
                position: "absolute",
                bottom: 0,
                left: 0,
                right: 0,
                background: "rgba(0,0,0,0.65)",
                display: "flex",
                justifyContent: "space-between",
                padding: "4px 6px",
              }}
            >
              {idx > 0 && (
                <button
                  type="button"
                  onClick={() => makeCover(idx)}
                  title="Make this primary cover"
                  style={{
                    background: "none",
                    border: "none",
                    color: "#E2C376",
                    fontSize: 10,
                    fontWeight: 600,
                    cursor: "pointer",
                    padding: 0,
                  }}
                >
                  Set Cover
                </button>
              )}
              <button
                type="button"
                onClick={() => removeImage(idx)}
                title="Remove photo"
                style={{
                  background: "none",
                  border: "none",
                  color: "#ff8b8b",
                  fontSize: 10,
                  fontWeight: 600,
                  cursor: "pointer",
                  marginLeft: "auto",
                  padding: 0,
                }}
              >
                Delete ✕
              </button>
            </div>
          </div>
        ))}

        {/* Dropzone / Upload button card */}
        <div
          onDragOver={(e) => {
            e.preventDefault();
            setDragActive(true);
          }}
          onDragLeave={() => setDragActive(false)}
          onDrop={(e) => {
            e.preventDefault();
            setDragActive(false);
            if (e.dataTransfer.files) handleUploadFiles(e.dataTransfer.files);
          }}
          onClick={() => fileInputRef.current?.click()}
          style={{
            aspectRatio: "1",
            borderRadius: 8,
            border: `2px dashed ${dragActive ? "#073D26" : "#C4BAA8"}`,
            background: dragActive ? "#F4EEDF" : "#FAFAF7",
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "center",
            cursor: "pointer",
            padding: 8,
            textAlign: "center",
            transition: "all 0.2s ease",
          }}
        >
          <span style={{ fontSize: 24, lineHeight: 1, marginBottom: 4, color: "#073D26" }}>+</span>
          <span style={{ fontSize: 11, fontWeight: 600, color: "#4A4031" }}>
            {uploading ? "Uploading…" : "Add Images"}
          </span>
          <span style={{ fontSize: 10, color: "#888", marginTop: 2 }}>
            Drag &amp; drop or click
          </span>
        </div>
      </div>

      <input
        ref={fileInputRef}
        type="file"
        multiple
        accept="image/jpeg,image/png,image/webp,image/avif"
        style={{ display: "none" }}
        onChange={(e) => {
          if (e.target.files) handleUploadFiles(e.target.files);
          e.target.value = "";
        }}
      />

      {/* Preset Quick-Add Toolbar */}
      <details style={{ marginTop: 8, fontSize: 12 }}>
        <summary style={{ cursor: "pointer", color: "#665C4E", fontWeight: 500 }}>
          Or select from pre-approved gallery presets ({DEFAULT_PRESETS.length}) ▾
        </summary>
        <div style={{ display: "flex", flexWrap: "wrap", gap: 6, marginTop: 8 }}>
          {DEFAULT_PRESETS.map((p) => {
            const isAdded = images.includes(p.url);
            return (
              <button
                key={p.url}
                type="button"
                onClick={() => addPreset(p.url)}
                disabled={isAdded}
                style={{
                  fontSize: 11,
                  padding: "3px 8px",
                  borderRadius: 4,
                  border: "1px solid",
                  borderColor: isAdded ? "#ddd" : "#C5A059",
                  background: isAdded ? "#f5f5f5" : "#fff",
                  color: isAdded ? "#999" : "#073D26",
                  cursor: isAdded ? "default" : "pointer",
                }}
              >
                {isAdded ? "✓ " : "+ "}
                {p.label}
              </button>
            );
          })}
        </div>
      </details>
    </div>
  );
}
