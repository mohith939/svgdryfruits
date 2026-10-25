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

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) {
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
        setErrorMsg(data.error || "Unable to submit message. Please try again.");
      } else {
        setSuccessMsg(data.message || "Thank you! Your message has been sent.");
        setForm({
          name: "",
          email: "",
          phone: "",
          subject: "Product Enquiry",
          message: "",
        });
      }
    } catch {
      setErrorMsg("Network error. Please try again or reach out on WhatsApp.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <motion.div
      className="contact-form-wrapper"
      initial={{ opacity: 0, y: 24 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-40px" }}
      transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
    >
      <div className="contact-form-header">
        <h2>Send us a <em>message.</em></h2>
        <p>Fill out the form below and our team will get back to you promptly.</p>
      </div>

      {successMsg && (
        <motion.div
          className="contact-success-banner"
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
        >
          <Icon name="shield" size={20} />
          <span>{successMsg}</span>
        </motion.div>
      )}

      {errorMsg && (
        <div className="checkout-error-banner" role="alert">
          <Icon name="shield" size={18} />
          <span>{errorMsg}</span>
        </div>
      )}

      <form className="contact-form-grid" onSubmit={handleSubmit}>
        <div className="form-group-row">
          <div className="form-group">
            <label htmlFor="c-name">Full name <span className="req">*</span></label>
            <input
              id="c-name"
              name="name"
              required
              value={form.name}
              onChange={handleChange}
              placeholder="e.g. Ananya Rao"
            />
          </div>

          <div className="form-group">
            <label htmlFor="c-email">Email address <span className="req">*</span></label>
            <input
              id="c-email"
              name="email"
              type="email"
              required
              value={form.email}
              onChange={handleChange}
              placeholder="ananya@example.com"
            />
          </div>
        </div>

        <div className="form-group-row">
          <div className="form-group">
            <label htmlFor="c-phone">Phone number <span className="opt">(optional)</span></label>
            <input
              id="c-phone"
              name="phone"
              type="tel"
              value={form.phone}
              onChange={handleChange}
              placeholder="10-digit mobile number"
            />
          </div>

          <div className="form-group">
            <label htmlFor="c-subject">Subject / Enquiry type <span className="req">*</span></label>
            <select
              id="c-subject"
              name="subject"
              value={form.subject}
              onChange={handleChange}
            >
              <option value="Product Enquiry">Product enquiry &amp; pack sizes</option>
              <option value="Order Status">Order status or delivery enquiry</option>
              <option value="Corporate / Festive Gifting">Corporate or festive gifting combo</option>
              <option value="General Feedback">General feedback or other question</option>
            </select>
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="c-msg">Your message <span className="req">*</span></label>
          <textarea
            id="c-msg"
            name="message"
            required
            rows={5}
            value={form.message}
            onChange={handleChange}
            placeholder="Tell us what you need or ask any questions about our products..."
          />
        </div>

        <div className="contact-form-submit-row">
          <motion.button
            type="submit"
            className="button button-primary contact-submit-btn"
            disabled={loading}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
          >
            {loading ? "Sending message…" : "Send message"} <span aria-hidden="true">↗</span>
          </motion.button>
        </div>
      </form>
    </motion.div>
  );
}
