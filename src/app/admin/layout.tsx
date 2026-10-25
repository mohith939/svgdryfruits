import { getAdminSession } from "@/lib/auth";
import { AdminTopNav } from "@/components/AdminTopNav";

export const dynamic = "force-dynamic";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const session = await getAdminSession();

  // If not logged in, render children directly (allows /admin/login to render cleanly)
  if (!session) {
    return <>{children}</>;
  }

  return (
    <div className="admin-shell">
      <AdminTopNav adminEmail={session.admin.email} />
      <main className="admin-main">
        {children}
      </main>
    </div>
  );
}
