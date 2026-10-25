import type { Metadata } from "next";
import { AdminLoginForm } from "@/components/AdminLoginForm";

export const metadata: Metadata = {
  title: "Login — SVG Dry Fruits",
  description: "Sign in to manage SVG Dry Fruits products, stock, and orders.",
};

export default function LoginPage() {
  return <AdminLoginForm />;
}
