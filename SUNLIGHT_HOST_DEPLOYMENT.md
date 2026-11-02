# Deploying SVG Dry Fruits on Sunlight Host (cPanel)

This guide walks you through deploying the **SVG Dry Fruits** Next.js application and connecting its PostgreSQL database on **Sunlight Host** (or any cPanel / CloudLinux Node.js hosting).

---

## 1. Create PostgreSQL Database on Sunlight Host

1. Log in to your **Sunlight Host cPanel**.
2. Under the **Databases** section, click **PostgreSQL Database Wizard** (or **PostgreSQL Databases**).
3. **Step 1 — Create a Database**:
   - Enter a name, e.g. `svgdryfruits`.
   - Your full database name will look like: `cpaneluser_svgdryfruits`.
4. **Step 2 — Create Database User**:
   - Username: e.g. `svguser` (full username: `cpaneluser_svguser`).
   - Password: Click *Password Generator* or enter a strong password. **Copy this password.**
5. **Step 3 — Add User to Database**:
   - Check **All Privileges** and click **Make Changes**.
6. **Note on Host & Port**:
   - Host: `localhost` (or `127.0.0.1`)
   - Port: `5432`

---

## 2. Configure "Setup Node.js App" in cPanel

1. Go to the **Software** section in cPanel and click **Setup Node.js App**.
2. Click **Create Application**:
   - **Node.js version**: Choose **20.x** (or **18.x** / **22.x**).
   - **Application mode**: `Production`.
   - **Application root**: `svgdryfruits` (or your domain folder, e.g. `svgdryfruits.com`).
   - **Application URL**: Select your domain (`svgdryfruits.com` or `www.svgdryfruits.com`).
   - **Application startup file**: `server.js`.
3. Click **Create** in the top right.

---

## 3. Upload Project Files

You can upload your files via **Git Version Control** or **File Manager**:

### Option A: Using Git Version Control in cPanel (Fastest)
1. In cPanel, click **Git™ Version Control** → **Create**.
2. Clone URL: `https://github.com/mohith939/svgdryfruits.git`
3. Repository Path: match your **Application root** (e.g. `/home/cpaneluser/svgdryfruits`).
4. Click **Create**.

### Option B: Using File Manager
1. In cPanel, open **File Manager**.
2. Navigate to your application root directory (e.g. `/home/cpaneluser/svgdryfruits`).
3. Upload your project files (do not upload `node_modules` or `.next`; they will be built on the server).

---

## 4. Set Environment Variables (`.env`)

In your application root on Sunlight Host, create or edit the `.env` file:

```env
# Database (using the cPanel PostgreSQL credentials from Step 1)
DATABASE_URL=postgresql://cpaneluser_svguser:YourStrongPassword@localhost:5432/cpaneluser_svgdryfruits
DB_SSL=false

# App Configuration
NODE_ENV=production
PORT=3000
NEXT_PUBLIC_SITE_URL=https://www.svgdryfruits.com

# Cashfree Payments (Production)
CASHFREE_CLIENT_ID=1499458c9b9b6e272481a2656918549941
CASHFREE_CLIENT_SECRET=your_cashfree_secret_key_here
CASHFREE_ENVIRONMENT=PRODUCTION
NEXT_PUBLIC_CASHFREE_CLIENT_ID=1499458c9b9b6e272481a2656918549941
NEXT_PUBLIC_CASHFREE_ENVIRONMENT=production

# Admin Login
ADMIN_BOOTSTRAP_EMAIL=svgdryfruits@gmail.com
ADMIN_BOOTSTRAP_PASSWORD=Mohith@123
ADMIN_SESSION_SECRET=svg-dry-fruits-secure-admin-session-secret-key-2026

# WhatsApp & Business Info
NEXT_PUBLIC_WHATSAPP_NUMBER=918340956469
NEXT_PUBLIC_CONTACT_EMAIL=svgdryfruits@gmail.com
```

---

## 5. Install Dependencies & Build

In cPanel **Setup Node.js App**:
1. At the top of your app page, cPanel shows an "Enter to the virtual environment" command like:
   ```bash
   source /home/cpaneluser/nodevenv/svgdryfruits/20/bin/activate && cd /home/cpaneluser/svgdryfruits
   ```
2. Open **Terminal** in cPanel (under the Advanced / Software section) and paste that command, or click the **Run JS Install** button in the cPanel UI to install `npm packages`.
3. In Terminal, build the Next.js production bundle:
   ```bash
   npm run build
   ```

---

## 6. Start / Restart Your Application

1. In **Setup Node.js App**, click **Restart**.
2. Visit `https://www.svgdryfruits.com`.

### Automatic Database Initialization
When your application starts for the first time:
- The app automatically detects the new Sunlight Host PostgreSQL database.
- It executes all tables (`admins`, `categories`, `products`, `variants`, `orders`, `coupons`, etc.).
- It automatically seeds all real categories, catalog dry fruit items, and pack variants.
- It bootstraps your admin account (`svgdryfruits@gmail.com` / `Mohith@123`).

No manual SQL scripts are required!

---

## 7. Useful Commands

- **Seed or update catalog**: `npm run db:seed`
- **Rebuild after updates**: `npm run build`
- **Restart Passenger app**: click **Restart** in cPanel or run `touch tmp/restart.txt`
