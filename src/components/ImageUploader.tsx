"use client";

import { useRef, useState } from "react";

interface ImageUploaderProps {
  value: string | null;
  onChange: (url: string) => void;
  label?: string;
  placeholder?: string;
  presetImages?: { label: string; url: string }[];
}

const DEFAULT_PRESETS = [
  { label: "Pistachios HD", url: "/assets/shop-pistachios-hd.jpg" },
  { label: "Almonds", url: "/assets/pdp-hero-almonds.jpg" },
  { label: "Cashews", url: "/assets/shop-cashews.jpg" },
  { label: "Walnuts", url: "/assets/shop-walnuts.jpg" },
  { label: "Medjool Dates", url: "/assets/shop-premium-dates.jpg" },
  { label: "Afghan Figs", url: "/assets/shop-dried-figs.jpg" },
  { label: "Golden Raisins", url: "/assets/shop-golden-raisins.jpg" },
  { label: "Turkish Apricots", url: "/assets/shop-dried-apricots.jpg" },
  { label: "Pumpkin Seeds", url: "/assets/shop-pumpkin-seeds.jpg" },
  { label: "Sunflower Seeds", url: "/assets/shop-sunflower-seeds.jpg" },
  { label: "Healthy Mix", url: "/assets/shop-mixed-nuts.jpg" },
  { label: "Gift Box", url: "/assets/mixed-fruit-reference.jpg" },
];

export function ImageUploader({
  value,
  onChange,
  label = "Product Image",
  presetImages = DEFAULT_PRESETS,
}: ImageUploaderProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showPresets, setShowPresets] = useState(false);
  const [dragOver, setDragOver] = useState(false);

  async function handleFile(file: File) {
    if (!file.type.startsWith("image/")) {
      setError("Please select an image file (JPEG, PNG, WebP, SVG).");
      return;
    }
    setError(null);
    setUploading(true);

    try {
      const form = new FormData();
      form.append("file", file);

      const res = await fetch("/api/admin/upload", {
        method: "POST",
        body: form,
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Failed to upload image.");
      } else {
        onChange(data.url);
      }
    } catch {
      setError("An unexpected error occurred during upload.");
    } finally {
      setUploading(false);
    }
  }

  function onDrop(e: React.DragEvent) {
    e.preventDefault();
    setDragOver(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  }

  return (
    <div className="image-uploader-box">
      <span className="image-uploader-label">{label}</span>

      <div className="image-uploader-main">
        {/* Preview box */}
        <div className="image-preview-container">
          {value ? (
            /* eslint-disable-next-line @next/next/no-img-element */
            <img src={value} alt="Preview" className="image-preview-img" />
          ) : (
            <div className="image-preview-placeholder">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#888" strokeWidth="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                <circle cx="8.5" cy="8.5" r="1.5" />
                <polyline points="21 15 16 10 5 21" />
              </svg>
              <span>No image selected</span>
            </div>
          )}
        </div>

        {/* Upload controls */}
        <div className="image-upload-controls">
          <div
            className={`image-dropzone ${dragOver ? "is-dragover" : ""} ${uploading ? "is-uploading" : ""}`}
            onDragOver={(e) => {
              e.preventDefault();
              setDragOver(true);
            }}
            onDragLeave={() => setDragOver(false)}
            onDrop={onDrop}
            onClick={() => fileInputRef.current?.click()}
          >
            <input
              type="file"
              ref={fileInputRef}
              style={{ display: "none" }}
              accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
              onChange={(e) => {
                if (e.target.files && e.target.files[0]) {
                  handleFile(e.target.files[0]);
                }
              }}
            />
            {uploading ? (
              <span className="upload-spinner-text">Uploading image…</span>
            ) : (
              <>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="17 8 12 3 7 8" />
                  <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                <span><strong>Click to upload</strong> or drag &amp; drop</span>
                <small>PNG, JPG, WebP up to 8MB</small>
              </>
            )}
          </div>

          <div className="image-url-or-presets">
            <div className="image-url-input-wrap">
              <input
                type="text"
                placeholder="Or paste an image URL (/assets/... or https://...)"
                value={value ?? ""}
                onChange={(e) => onChange(e.target.value)}
                className="image-url-field"
              />
              {value && (
                <button
                  type="button"
                  className="image-clear-btn"
                  onClick={() => onChange("")}
                  title="Remove image"
                >
                  ✕
                </button>
              )}
            </div>

            <button
              type="button"
              className="button-text-toggle"
              onClick={() => setShowPresets(!showPresets)}
            >
              {showPresets ? "Hide preset gallery ▲" : "Choose from catalog gallery ▼"}
            </button>
          </div>
        </div>
      </div>

      {error && <p className="image-uploader-error">{error}</p>}

      {/* Preset Gallery */}
      {showPresets && (
        <div className="preset-gallery-grid">
          {presetImages.map((p) => (
            <button
              key={p.url}
              type="button"
              onClick={() => {
                onChange(p.url);
                setShowPresets(false);
              }}
              className={`preset-thumb-btn ${value === p.url ? "is-active" : ""}`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={p.url} alt={p.label} />
              <span>{p.label}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
