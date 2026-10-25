const instagramHost = /^(www\.)?instagram\.com$/i;

function safeInstagram(u: string): string | null {
  try {
    const url = new URL(u.trim());
    return url.protocol === "https:" && instagramHost.test(url.hostname) ? url.toString() : null;
  } catch {
    return null;
  }
}

export function instagramProfile() {
  return safeInstagram(process.env.NEXT_PUBLIC_INSTAGRAM_URL ?? "");
}

export function instagramPosts() {
  return (process.env.NEXT_PUBLIC_INSTAGRAM_POSTS ?? "").split(",").map(safeInstagram).filter((u): u is string => !!u).slice(0, 6);
}
