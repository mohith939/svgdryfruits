// All money is stored and computed as integer paise.
export function rupeesToPaise(rupees: number): number {
  return Math.round(rupees * 100);
}

export function formatINR(paise: number): string {
  const rupees = paise / 100;
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    minimumFractionDigits: Number.isInteger(rupees) ? 0 : 2,
  }).format(rupees);
}

export function assertPaise(value: number): number {
  if (!Number.isSafeInteger(value) || value < 0) {
    throw new Error("Amount must be a non-negative integer number of paise");
  }
  return value;
}
