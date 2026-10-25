import Link from "next/link";

export function PageShell({ kicker, title, children, draft = false }: {
  kicker: string; title: React.ReactNode; children: React.ReactNode; draft?: boolean;
}) {
  return (
    <article className="content-page">
      <nav className="breadcrumbs" aria-label="Breadcrumb"><Link href="/">Home</Link><span aria-hidden="true">/</span><span>{kicker}</span></nav>
      <p className="section-kicker">{kicker.toUpperCase()}</p>
      <h1>{title}</h1>
      {draft && (
        <p className="content-note">Draft for the owner to review. Specific commitments, timelines and legal wording must be confirmed by SVG Dry Fruits before launch.</p>
      )}
      <div className="content-body">{children}</div>
    </article>
  );
}
