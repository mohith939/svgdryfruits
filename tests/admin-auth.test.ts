import { describe, expect, it } from "vitest";
import { hashPassword, verifyPassword } from "@/lib/auth";

describe("admin authentication", () => {
  it("hashes password with salt and verifies correctly", () => {
    const raw = "SuperSecretPassword123!";
    const hashed = hashPassword(raw);

    expect(hashed).toContain(":");
    expect(verifyPassword(raw, hashed)).toBe(true);
    expect(verifyPassword("WrongPassword", hashed)).toBe(false);
  });

  it("produces different hashes for the same password due to unique salts", () => {
    const raw = "SamePassword456";
    const h1 = hashPassword(raw);
    const h2 = hashPassword(raw);

    expect(h1).not.toBe(h2);
    expect(verifyPassword(raw, h1)).toBe(true);
    expect(verifyPassword(raw, h2)).toBe(true);
  });
});
