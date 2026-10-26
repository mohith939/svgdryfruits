"use client";

import { useState } from "react";
import { motion } from "motion/react";
import { Icon } from "@/components/Icon";

export function ContactForm() {
  const [form, setForm] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "Product Enquiry",
    message: "",
  });

  const [loading, setLoading] = useState(false);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  function handleChange(
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) {
    const { name, value } = e.target;
    setForm((f) => ({ ...f, [name]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setErrorMsg(null);
    setSuccessMsg(null);

    try {
      const res = await fetch("/api/contact", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify(form),
      });

      const data = await res.json();
      if (!res.ok) {
        setErrorMsg(data.error || "Unable to send message. Please try again.");
      } else {
        setSuccessMsg(
          data.message ||
            "Thank you! Your message has been received. Our team will get back to you shortly."
        );
        setForm({
          name: "",
          email: "",
          phone: "",
          subject: "Product Enquiry",
          message: "",
        });
      }
    } catch {
      setErrorMsg("Network connection error. Please try again or reach out on WhatsApp.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-contact-form-box">
      <div className="min-contact-form-head">
        <h2>Send a Message</h2>
        <p>Leave a note below and our team will get back to you within 2–4 hours.</p>
      </div>

      {successMsg && (
        <motion.div
          className="contact-success-banner"
          style={{
            borderRadius: 8,
            marginBottom: 20,
            background: "#EAF7EE",
            borderColor: "#A3D9B5",
            color: "#145A2E",
          }}
          initial={{ opacity: 0, y: -6 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <Icon name="shield" size={18} />
          <span>{successMsg}</span>
        </motion.div>
      )}

      {errorMsg && (
        <div
          className="checkout-error-banner"
          style={{ borderRadius: 8, marginBottom: 20 }}
          role="alert"
        >
          <Icon name="shield" size={18} />
          <span>{errorMsg}</span>
        </div>
      )}

      <form className="min-form-grid" onSubmit={handleSubmit}>
        <div className="min-form-row">
          <div className="min-form-group">
            <label htmlFor="c-name">
              Full Name <span className="req">*</span>
            </label>
            <input
              id="c-name"
              name="name"
              required
              value={form.name}
              onChange={handleChange}
              placeholder="e.g. Ananya Sharma"
              className="min-form-input"
            />
          </div>

          <div className="min-form-group">
            <label htmlFor="c-email">
              Email Address <span className="req">*</span>
            </label>
            <input
              id="c-email"
              name="email"
              type="email"
              required
              value={form.email}
              onChange={handleChange}
              placeholder="ananya@example.com"
              className="min-form-input"
            />
          </div>
        </div>

        <div className="min-form-row">
          <div className="min-form-group">
            <label htmlFor="c-phone">
              Phone Number <span className="opt">(Optional)</span>
            </label>
            <input
              id="c-phone"
              name="phone"
              type="tel"
              value={form.phone}
              onChange={handleChange}
              placeholder="+91 98450 12345"
              className="min-form-input"
            />
          </div>

          <div className="min-form-group">
            <label htmlFor="c-subject">
              Topic / Purpose <span className="req">*</span>
            </label>
            <select
              id="c-subject"
              name="subject"
              value={form.subject}
              onChange={handleChange}
              className="min-form-select"
            >
              <option value="Product Enquiry">Product details &amp; pack sizes</option>
              <option value="Corporate / Festive Gifting">Corporate &amp; bulk gifting</option>
              <option value="Order Status">Order status &amp; tracking support</option>
              <option value="Partnership / Wholesale">Wholesale &amp; partnership</option>
              <option value="General Feedback">General feedback / Other</option>
            </select>
          </div>
        </div>

        <div className="min-form-group">
          <label htmlFor="c-msg">
            Your Message <span className="req">*</span>
          </label>
          <textarea
            id="c-msg"
            name="message"
            required
            rows={4}
            value={form.message}
            onChange={handleChange}
            placeholder="Tell us what you're looking for or how we can assist you..."
            className="min-form-textarea"
          />
        </div>

        <button
          type="submit"
          className="min-form-btn"
          disabled={loading}
        >
          {loading ? (
            "Sending message…"
          ) : (
            <>
              <span>Send Message</span>
              <span aria-hidden="true" style={{ fontSize: 16 }}>→</span>
            </>
          )}
        </button>
      </form>
    </div>
  );
}
