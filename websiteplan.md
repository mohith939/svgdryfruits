SVG DRY FRUITS — COMPLETE A–Z MASTER BUILD PROMPT
1. YOUR ROLE AND MISSION
You are my senior full-stack software architect, premium ecommerce UI/UX designer, frontend animation specialist, backend engineer, PostgreSQL database architect, payment integration engineer, security engineer, QA automation engineer, and deployment specialist.
Your task is to help me build a complete, production-oriented ecommerce website for my Indian dry-fruits brand, SVG Dry Fruits.
I do not want a simple demo, a static landing page, a generic ecommerce template, a UI mockup, or disconnected components. I want a cohesive, premium, fully functional online store that I can develop in my existing VS Code repository, test, deploy, and operate as a real business.
You must handle the project from A to Z:
Inspect the existing repository, files, dependencies, framework, configuration, and brand assets.
Preserve and use my existing SVG Dry Fruits logo.
Design a premium, distinctive, human-crafted shopping experience.
Build the complete responsive storefront.
Implement the product catalog, fixed-weight variants, product images, shopping cart, discounts, inventory, and gift combos.
Implement guest checkout and secure Razorpay payments.
Build a secure administrator dashboard.
Implement order management, shipment tracking, customer order tracking, and manual refund recording.
Create the database schema, migrations, validation, authorization, and server-side business logic.
Implement appropriate animations and micro-interactions.
Add testing, accessibility, SEO, performance optimization, documentation, and deployment instructions.
Finish with a clear report of what works, what was tested, and what remains dependent on external configuration.
Work directly in the repository. Do not merely tell me what code I should write. Inspect the actual project, create and modify the necessary files, run the available checks, and implement the solution.
Work independently through the implementation stages without asking me to approve every small decision. Ask me only when a decision genuinely requires information that cannot reasonably be inferred. If a secret, business detail, or external account is unavailable, implement the appropriate integration with clearly documented environment variables and placeholders instead of inventing credentials.
Never claim a feature is complete or a test has passed unless the implementation or test results support that claim.

2. PROJECT IDENTITY AND BUSINESS MODEL
Brand: SVG Dry Fruits
Business: A single-vendor Indian dry-fruits and nuts ecommerce store selling products directly to customers across India.
Primary goal: Create a beautiful, trustworthy, high-converting online shopping experience that makes customers feel they are buying premium-quality products from a genuine Indian brand.
Target audience:
Families buying everyday dry fruits and nuts.
Health-conscious shoppers.
Customers buying premium almonds, cashews, pistachios, raisins, dates, walnuts, and related products.
Customers shopping for gifts and festive hampers.
Customers looking for attractive combination packs and value bundles.
Initial operating model:
One seller and one administrator-controlled catalog.
Customers check out as guests.
Fixed product weights: 250 g, 500 g, and 1 kg, where enabled for each product.
Razorpay is the only payment gateway.
No cash on delivery.
No customer account registration or login.
No automatic courier integration initially.
Shipping is configured and managed by the administrator.
Refunds are initiated manually through the Razorpay dashboard.
No automatic order emails or WhatsApp order notifications.
A floating WhatsApp support button is available.
Products, pricing, stock, discounts, orders, and delivery status are managed through the administrator dashboard.
The site should be structured so additional features can be added later without rewriting the core application.
Do not invent the business address, FSSAI number, GSTIN, certifications, sourcing claims, health claims, shipping promises, or legal commitments. Keep unknown business information configurable or clearly marked for the owner to complete.

3. FIRST ACTION: INSPECT THE EXISTING REPOSITORY
Before writing or replacing code, inspect the entire relevant project structure.
Identify:
Existing framework and version.
React and Next.js versions, if applicable.
JavaScript or TypeScript configuration.
Package manager and lockfile.
Existing application routes.
Current design system and CSS conventions.
Existing components and utilities.
Existing authentication implementation, if any.
Database configuration and migrations, if any.
Payment integration, if any.
Image and file storage configuration.
Existing deployment settings.
Available tests and linting tools.
Existing logo, product images, fonts, and brand assets.
Environment-variable conventions.
Existing Git changes that must not be overwritten.
Inspect the logo file and preserve its actual appearance.
Repository rules
Keep useful existing code where possible.
Extend the existing architecture rather than replacing everything unnecessarily.
Do not overwrite unrelated work or destroy existing configuration.
Do not expose secrets or copy .env credentials into source files.
Do not install packages blindly.
Check package compatibility before introducing dependencies.
Use the existing package manager and respect its lockfile.
If the repository is empty, initialize a clean, documented project structure.
If an important asset is missing, create a clearly named asset location and document what must be supplied.
Never pretend that a missing image, payment credential, database, or account is already available.
After inspection, establish a practical implementation plan and proceed with the actual implementation.

4. VISUAL IDENTITY AND ART DIRECTION
The website must feel like a carefully art-directed premium Indian dry-fruits brand.
It should combine the visual warmth of natural food photography with the elegance of a luxury retail brand.
The design should be distinctive, composed, inviting, polished, and commercially useful.
4.1 Brand color palette
Use the following palette as the main design foundation:
Deep emerald green: #073D26
Champagne gold: #C9A34E
Warm ivory: #F8F3E9
Natural brown: #4A2D20
Supporting shades may be introduced for readability, borders, subtle backgrounds, and interaction states. Keep the core brand identity consistent.
Color usage
Deep emerald green: hero sections, navigation accents, primary buttons, brand moments, premium promotional sections.
Champagne gold: restrained decorative details, small highlights, separators, icons, selected states, and premium packaging accents.
Warm ivory: main shopping surfaces, product cards, content sections, and spacious backgrounds.
Natural brown: food-related details, secondary typography, warm accents, and editorial content.
White or near-white: forms, readable surfaces, and contrast where necessary.
Do not make every section dark green. Alternate between emerald, ivory, and warm neutral surfaces to create rhythm.
Gold must feel elegant, not excessive. Preserve accessible text contrast.
4.2 Logo requirements
Use my existing SVG Dry Fruits logo as the authoritative brand asset.
Locate the existing logo before designing around it.
Preserve its original proportions, colors, artwork, and lettering.
Do not redraw it, generate a replacement, distort it, or convert it into unrelated artwork.
Use the original asset wherever possible.
If the source logo is SVG, keep it as SVG.
If the source is raster, use the highest-quality original available.
Use suitable CSS sizing and object-fit: contain.
Do not add unnecessary effects that make the logo illegible.
Provide sensible dimensions for desktop and mobile.
Use the logo in the header and footer and in other appropriate brand contexts.
If the file cannot be found, create a clearly documented placeholder path and ask me to add the original logo there. Do not silently substitute a different logo.
4.3 Typography
Use a refined typographic system with two complementary roles:
Display typography: An elegant editorial or luxury serif for prominent headlines and selected section titles.
Functional typography: A clean, highly readable sans-serif for navigation, descriptions, prices, product variants, forms, buttons, and administrative interfaces.
Use appropriate licensed fonts or a reliable fallback stack. Prefer optimized font loading and avoid unnecessarily loading many font families or weights.
Typography must have:
A clear hierarchy.
Comfortable line height.
Deliberate text widths.
Consistent spacing.
Readable mobile sizes.
Strong contrast between headlines, descriptions, prices, and labels.
Avoid using the same typography size and weight for every element.
4.4 Human-crafted design standard
The site must not look like a generic AI-generated ecommerce template.
Avoid:
Repeating the same card grid in every section.
Using excessive rounded corners on every component.
Excessive gradients, glassmorphism, and blur effects.
Random floating circles, meaningless sparkles, and decorative blobs.
Oversized empty sections that add no value.
Repetitive icon-and-text blocks.
Generic marketing slogans repeated across the page.
Fake reviews, fabricated customer counts, invented awards, or made-up trust badges.
Random animations that distract from shopping.
Unnecessary carousels and autoplay elements.
Stock imagery that does not match the product being sold.
Buttons that look functional but do nothing.
Use deliberate composition, varied section layouts, strong product imagery, carefully controlled whitespace, restrained decoration, and consistent alignment.
Every section must have a clear purpose.

5. TECHNICAL ARCHITECTURE
Use the following preferred stack, adapting to the existing repository where necessary.
Frontend
Next.js using a version and rendering model compatible with the project.
React.
TypeScript.
Semantic HTML.
Accessible, reusable React components.
CSS modules, the existing styling system, or Tailwind CSS if appropriate for the repository.
Backend
Next.js server-side functionality, Route Handlers, or another appropriate server layer already present in the project.
Server-side input validation.
Explicit authorization checks.
Business logic separated from presentation components.
Reliable payment verification and order-state management.
Database
PostgreSQL.
A suitable typed ORM or database access layer, selected after inspecting the repository.
Versioned database migrations.
Appropriate indexes, constraints, transactions, and referential integrity.
Deployment
Cloudflare Workers Free is the intended initial deployment target.
A compatible Next.js deployment adapter or framework path must be selected based on current official compatibility documentation.
Cloudflare R2 or another compatible persistent object-storage service may be used for product images.
PostgreSQL will need an appropriate external database provider or compatible database service; do not assume Cloudflare Workers includes a conventional PostgreSQL server.
Architecture rules
Keep components modular and understandable.
Separate UI, domain logic, database access, authentication, payments, inventory, shipping, and administration.
Use server-side code for privileged operations.
Keep secrets exclusively on the server.
Use strict TypeScript where practical.
Avoid unnecessary abstractions and overengineering.
Avoid building a monolithic file containing the entire application.
Avoid duplicating business rules across multiple routes.
Avoid fragile client-only security.
Avoid unmaintained dependencies.
Provide a clear .env.example with descriptions and placeholders, never real secrets.
Document every required external service and setup step.
Before selecting the database provider, evaluate its current free-tier terms, connectivity from the selected Cloudflare runtime, connection limits, sleeping or expiry behavior, backups, and likely production costs. Prefer a practical, low-cost initial configuration, but never promise that a provider will remain free indefinitely.

6. COMPLETE SITE STRUCTURE
Implement the following customer-facing routes, adapting route names to the project's conventions.
/ — Homepage.
/shop — Complete product catalog.
/categories/[slug] — Category product listings.
/products/[slug] — Product details.
/combos — Gift hampers and combination packs.
/cart — Shopping cart.
/checkout — Guest checkout and payment.
/order/success — Verified order confirmation.
/track-order — Customer order tracking.
/about — About the brand.
/contact — Contact information and WhatsApp support.
/privacy-policy — Privacy policy.
/terms-and-conditions — Terms and conditions.
/shipping-policy — Shipping policy.
/returns-and-refunds — Returns and refunds policy.
Implement suitable error states, loading states, empty states, not-found pages, and server-error handling.
Do not expose internal administrative routes or private order information to unauthenticated visitors.

7. PREMIUM HOMEPAGE — COMPLETE ART DIRECTION
The homepage is the most important visual page.
Build it as a carefully composed premium shopping experience inspired by high-end food, gourmet gifting, and specialty retail websites.
Use the previously approved creative direction as the starting point: deep emerald green, champagne gold, warm ivory, the original logo, premium dry-fruit photography, clean product cards, and gift-hamper imagery.
Do not reproduce a reference mechanically. Adapt the design to the actual assets and build a coherent, distinctive interface.
7.1 Announcement strip
Create a compact announcement strip for a real, configurable business announcement.
Examples may include a shipping offer or a seasonal promotion, but do not activate an offer unless its terms are configured.
Keep the text short.
Use a restrained background and typography.
Make it responsive.
Do not use an invented countdown timer.
Do not show a false free-shipping offer.
7.2 Navigation header
Create a refined responsive header containing:
Original SVG Dry Fruits logo.
Home link.
Shop link.
Categories navigation.
Combos or gifting link.
About link.
Search control.
Cart icon with an accurate item count.
Mobile navigation menu.
The header should feel premium, not crowded.
Use a light ivory or brand-appropriate background. Apply a subtle shadow, border, or sticky behavior only when it improves usability.
Requirements:
Correct active navigation states.
Working search interaction.
Working cart navigation.
Accessible keyboard navigation.
Clear mobile menu behavior.
No fake account icon if customer accounts are not implemented.
No broken menu links.
Prevent layout shifts when the header changes state.
7.3 Cinematic hero section
Create a visually striking hero that immediately communicates premium quality.
The hero should feature:
Deep emerald green as the dominant brand surface.
A strong editorial headline.
A short supporting sentence.
A primary shopping call to action.
A secondary action for exploring products or gift combos.
Premium dry-fruit photography or a carefully composed product still life.
Subtle champagne-gold accents.
Carefully balanced text and imagery.
Responsive composition for mobile, tablet, and desktop.
A headline direction could be:
“Pure Goodness in Every Bite”
Treat this as sample copy, not a requirement to repeat it throughout the website.
The imagery should emphasize the actual products, natural textures, rich colors, premium packaging, and believable lighting.
Use motion to introduce the headline and imagery with restraint. The hero must still look excellent if animations are disabled.
Do not use a giant generic gradient, random floating objects, or decorative particles as a substitute for art direction.
If suitable assets are missing, create a clear image manifest and use carefully selected temporary assets only when licensing and source quality are appropriate. Never represent placeholder assets as genuine product photography.
7.4 Category showcase
Create a visually attractive category section that allows shoppers to explore the catalog quickly.
Potential categories:
Almonds.
Cashews.
Pistachios.
Walnuts.
Raisins.
Dates.
Mixed dry fruits.
Gift hampers and combos.
Only display categories that exist or are intentionally seeded.
Each category should have:
Relevant imagery.
A concise label.
A working destination.
A deliberate visual treatment.
Avoid making every category a giant identical rounded card. Use an editorial arrangement, varied image crops, and consistent spacing.
On mobile, ensure that all categories remain discoverable and easy to tap.
7.5 Bestsellers and featured products
Build a product showcase featuring administrator-selected products.
Each product card should include:
Product image.
Product name.
Selected or starting pack price, accurately labeled.
Available pack sizes, if appropriate for the layout.
Stock-aware availability.
Quick-add action where selection is unambiguous.
A working product-details link.
Optional featured or bestseller label only when assigned in the database.
Use warm ivory surfaces, restrained green details, subtle gold accents, and elegant hover effects.
Product photography is the visual priority. Prices and purchase actions must remain easy to find.
Do not fabricate ratings, review counts, or popularity statistics.
Quick-add must not silently choose an unexpected weight. If a product has multiple pack sizes and no default is explicitly configured, ask the shopper to select a variant before adding it.
Show a useful empty state if no products have been featured.
7.6 Gift hampers and combos
Create a premium promotional section for gift boxes, curated combinations, and festive gifting.
Visual direction:
Emerald green and warm ivory composition.
Premium packaging imagery.
Restrained gold details.
A clear headline.
Short copy.
A working link to /combos.
Support real combo products managed through the administrator dashboard.
Do not advertise a discount unless the combo's actual configured price and comparison price support that claim.
7.7 Brand benefits section
Create a compact trust-building section with concise statements based only on verified business practices.
Potential themes include:
Carefully selected products.
Freshness-conscious packing.
Secure online payments.
Convenient pack sizes.
Gift-ready options, if actually offered.
These are content directions, not permission to claim certifications, sourcing standards, or operational processes that the business has not verified.
Use elegant typography and a restrained layout. Do not turn the entire section into a repetitive collection of generic icons.
7.8 Lifestyle and editorial section
Add a warm editorial section that helps shoppers imagine using the products in everyday life, gifting, family occasions, or festive celebrations.
Use believable photography and short, natural copy.
Avoid unsupported medical, weight-loss, disease-prevention, or exaggerated nutrition claims.
If nutritional information is displayed, use verified product data. Do not invent calories, ingredients, allergen statements, or health benefits.
7.9 Final call to action
Create a concise final shopping prompt that leads to the shop or gift collection.
Avoid repeating every previous section's marketing copy.
7.10 Footer
Build a complete footer with:
Original brand logo.
Short brand description.
Shop links.
Category links.
About and contact links.
Shipping, returns, privacy, and terms links.
Configurable business contact details.
Configurable WhatsApp contact.
Social links only when genuine URLs are supplied.
Copyright notice generated from the current year.
Accessible contrast and mobile layout.
Do not invent social accounts, business addresses, or phone numbers.

8. PRODUCT CATALOG AND SHOPPING EXPERIENCE
8.1 Product catalog
The /shop page must display real database-backed products.
Include:
Product grid.
Product images.
Product name.
Accurate pricing.
Available variants.
Availability status.
Product detail links.
Category navigation.
Search.
Pagination or a sensible loading strategy when needed.
Do not add advanced filters, ratings, or reviews to the initial scope unless the existing project already has them and they can be maintained correctly.
8.2 Product search
Implement functional product search.
Search should:
Match relevant product names.
Support category names and sensible descriptive fields where appropriate.
Handle empty queries.
Handle no-result states.
Be safe against malformed input.
Use a reasonable server-side strategy as the catalog grows.
Do not build a search field that only looks functional.
8.3 Product detail page
Each product page should show:
Product name.
Main product image.
Additional product images when available.
Product description.
Available fixed-weight variants.
Price for the selected variant.
Stock availability.
Quantity selector.
Add-to-cart action.
Relevant product category.
Clear handling of unavailable products.
Relevant gift or related-product links where data supports them.
Update the displayed price immediately when a shopper selects a different pack size.
Prevent invalid quantities, unavailable variants, and quantities that exceed the applicable stock rules.
Do not claim that a product is organic, premium grade, pesticide-free, certified, or sourced from a particular region unless the administrator has entered verified information.
8.4 Fixed-weight variants
Support fixed pack sizes:
250 g.
500 g.
1 kg.
Each product may have one, two, or all three sizes available.
Each variant must have its own:
Unique variant identifier.
Weight.
Price.
Stock quantity.
Availability status.
SKU, if used.
Optional image association, if needed.
Do not assume every product has all variants.
Use integer paise for INR monetary calculations where appropriate. Avoid floating-point arithmetic for payment amounts.
The server must calculate the final price from trusted database records. Never trust prices, discounts, totals, or stock quantities supplied by the browser.
8.5 Product image management
Administrators must be able to:
Upload multiple images for a product.
Select a featured image.
Reorder gallery images.
Remove an image.
Replace an image.
Associate an image with the appropriate product.
See image previews before saving.
Use persistent object storage suitable for the chosen deployment environment, such as Cloudflare R2.
Do not rely on an ephemeral local application filesystem for production product uploads.
Implement:
File type validation.
File size limits.
Secure upload authorization.
Server-side verification where applicable.
Safe file naming.
Reasonable image dimensions and formats.
Image optimization or responsive image delivery.
Storage cleanup when images are deleted or replaced.
Never trust a filename or browser-supplied MIME type alone.
Do not expose storage credentials in client-side code.
8.6 Product and category administration
The administrator must be able to:
Create a product.
Edit a product.
Archive or deactivate a product.
Manage the slug.
Manage descriptions.
Assign categories.
Upload and reorder images.
Add, edit, and disable weight variants.
Change prices.
Adjust inventory.
Mark products as featured.
Manage combo products.
Set availability.
Configure low-stock thresholds.
Use sensible slug generation, collision checks, validation, and confirmation before destructive operations.
Prefer archiving products that are referenced by historical orders rather than deleting important historical records.

9. CART SYSTEM
Build a fully functional cart.
9.1 Guest cart persistence
Customers must be able to add items without creating an account.
Persist the guest cart in browser storage so it survives ordinary refreshes and navigation.
The cart must handle:
Adding a product variant.
Increasing or decreasing quantity.
Removing an item.
Emptying the cart.
Selecting fixed-weight variants.
Updating quantities.
Displaying the current subtotal.
Displaying the number of items.
Displaying stock-related warnings.
Store only the minimum information needed to reconstruct the cart, such as variant identifiers and quantities. Revalidate products, prices, availability, and stock against the server before checkout.
Treat browser storage as untrusted input. Never accept its prices or calculated totals as authoritative.
9.2 Cart totals
Display:
Product subtotal.
Applicable discount.
Shipping charge.
Final amount payable.
Explain discounts and shipping clearly.
All monetary calculations must be performed or revalidated on the server.
Handle:
Deleted products.
Disabled variants.
Price changes.
Stock changes.
Expired coupons.
Invalid quantities.
Empty carts.
Unavailable combos.
Show a helpful message when an item needs to be updated instead of silently charging an unexpected amount.

10. DISCOUNTS AND COUPONS
Implement an administrator-managed discount system.
Support:
Automatic discounts.
Coupon codes.
Percentage discounts.
Fixed-amount discounts.
Minimum cart value.
Start and expiry dates.
Active and inactive status.
Usage limits.
Per-coupon redemption tracking.
Clear discount descriptions.
Use server-side validation for every discount.
Prevent:
Expired coupon use.
Inactive coupon use.
Reuse beyond configured limits.
Invalid percentage values.
Negative discounts.
Discounts greater than the eligible amount.
Race conditions in limited-use coupons.
Unintended coupon stacking.
Do not stack discounts unless an explicit configuration allows it.
Validate coupon eligibility again at checkout and protect redemption limits transactionally.
Display a useful error when a coupon cannot be applied.

11. GIFT COMBOS AND INVENTORY
Gift combos are products with their own presentation and selling price.
Administrators must be able to:
Create a combo.
Assign a name, description, slug, and images.
Select the component products and variants.
Define quantities of each component.
Set the selling price.
Control visibility.
Mark a combo as featured.
Inventory must remain consistent between combos and their component products.
If a combo contains two packs of almonds and one pack of cashews, the system must account for the component inventory correctly.
Do not allow an item to be oversold because it is available through both an individual listing and a combo.
Use transactional inventory validation and reservations for all relevant components. When necessary, represent combo contents in a normalized database relationship rather than a text field.

12. INVENTORY MANAGEMENT
Track stock per product variant.
Provide:
Current stock.
Stock adjustment.
Availability status.
Low-stock threshold.
Low-stock indicator.
Out-of-stock indicator.
Inventory movement history where appropriate.
Protection against negative stock.
Safe handling of concurrent checkouts.
Do not allow customers to purchase unavailable stock.
The server must validate inventory when adding an item to a checkout and again when creating or confirming an order, as appropriate.
Do not use a simplistic read-then-write stock update that allows two simultaneous orders to purchase the same final unit.
Use transactions, atomic updates, row locking, conditional updates, or another database-supported concurrency-safe approach.
Create an explicit policy for cancellations, expired reservations, failed payments, and manual refunds.

13. SHIPPING CONFIGURATION
Shipping will initially be managed manually.
Provide administrator settings for:
Flat shipping charge.
Free-shipping threshold.
Optional regional shipping rates.
Configurable delivery estimate text.
Active or inactive shipping rules.
Do not claim that delivery is available to every Indian PIN code unless actual serviceability data exists.
Validate Indian PIN code format, but do not treat a syntactically valid PIN as proof that delivery is possible.
Collect the customer's shipping address in a structured form.
Keep shipping calculations centralized in server-side business logic so the cart, checkout, order summary, and admin order details use consistent rules.
Store a snapshot of the applicable shipping charge and relevant pricing at the time the order is created.
Do not silently change an existing order's shipping amount because the administrator later edits shipping settings.
Design a clean integration boundary so a courier provider can be added later without rewriting order management.

14. GUEST CHECKOUT
The store must support guest checkout without customer registration.
14.1 Required customer fields
Collect:
Full name.
Indian mobile number.
Email address.
PIN code.
State.
City or town.
Street address.
Optional landmark.
Use appropriate input types, labels, validation messages, and mobile-friendly layouts.
Validate email and phone formats sensibly. Normalize data where appropriate.
Validate the PIN code's format, but do not infer that the PIN is serviceable.
Do not require account creation.
14.2 Checkout summary
Display:
Product names.
Selected weights.
Quantities.
Item prices.
Subtotal.
Discount.
Shipping.
Final amount payable.
Shipping address.
Payment method.
The final amount must be computed from trusted server-side data.
14.3 Checkout flow
Implement a secure and understandable flow:
Validate the cart.
Validate each product and variant.
Recalculate product prices.
Validate quantities and available stock.
Revalidate the discount.
Calculate shipping.
Reserve inventory transactionally.
Create an order in an appropriate pending-payment state.
Create the Razorpay order securely on the server.
Return only the required public payment details to the client.
Open the Razorpay Checkout interface.
Send the resulting payment details to the server.
Verify the payment signature on the server.
Confirm the payment through a trusted gateway verification path.
Update the order and inventory in an idempotent, transaction-safe way.
Show the confirmation page only after the application has established the correct payment state.
If any step fails, preserve a consistent order state and show an understandable error.
Never mark an order paid because the browser says payment succeeded.

15. RAZORPAY INTEGRATION
Razorpay is the only initial payment gateway.
Use official Razorpay documentation and a maintained integration method compatible with the project.
Implement:
Server-side Razorpay order creation.
Correct amount in INR paise.
Unique order identifiers.
Payment Checkout integration.
Server-side signature verification.
Appropriate payment-status verification.
Webhook handling where appropriate.
Webhook signature validation.
Idempotent processing.
Duplicate-callback protection.
Payment failure handling.
Payment retry handling.
Reconciliation for uncertain payment outcomes.
Secure environment-variable management.
Never expose the Razorpay secret key in browser code.
Use environment variables such as:
RAZORPAY_KEY_ID
RAZORPAY_KEY_SECRET
RAZORPAY_WEBHOOK_SECRET
Use a suitable public key configuration only where needed by the checkout client. Validate required configuration on the server and provide clear startup or setup errors when configuration is missing.
Do not hardcode real credentials.
Do not store card numbers, CVVs, or other sensitive payment instrument data.
15.1 Payment states
Model payment states explicitly, for example:
Pending.
Authorized, if applicable.
Paid or captured.
Failed.
Expired.
Refund pending.
Partially refunded, if the chosen refund record model supports it.
Refunded.
Choose the actual transitions according to Razorpay's documented payment lifecycle.
Do not confuse the payment state with the order-fulfillment state.
15.2 Webhooks and reconciliation
Webhook processing must:
Validate the signature against the raw request body as required by the official integration.
Reject invalid signatures.
Record event identifiers.
Avoid duplicate processing.
Apply safe state transitions.
Handle events arriving out of order.
Avoid double-deducting inventory.
Preserve an audit trail.
Do not assume that a successful browser redirect is the only evidence of payment.
If the application cannot determine the payment result, keep the order in an appropriate unresolved state and provide a safe reconciliation path.

16. INVENTORY RESERVATIONS AND PAYMENT RACE CONDITIONS
This is a critical requirement.
Build an inventory reservation mechanism with expiration.
When a customer starts checkout:
Validate available stock.
Reserve the required stock within a database transaction.
Associate reservations with the relevant order or checkout.
Record reservation creation and expiry.
Prevent other checkouts from consuming reserved units.
Release expired reservations safely.
Ensure retries do not create duplicate reservations.
When payment succeeds:
Confirm the order.
Convert the reservation into a committed sale or otherwise apply an equivalent exactly-once inventory transition.
Mark the reservation as consumed.
Ensure the stock cannot be deducted twice.
When payment fails or the reservation expires:
Release the reservation safely when permitted by the actual payment state.
Do not release stock prematurely if a payment may still have succeeded.
Reconcile uncertain payment results before deciding whether inventory can be released.
Late payment handling
A payment may succeed after a reservation expires.
The application must not blindly confirm the order and deduct stock if the inventory has already been sold to another customer.
Implement a documented policy for this case. For example, reconcile the payment, attempt to allocate stock safely, and send the order into an explicit manual-review or refund-required state when fulfillment is impossible.
Since refunds are manual, never claim that an automatic refund has occurred. Record the required action for the administrator.
Idempotency
Ensure repeated callbacks, duplicated requests, and retry attempts cannot:
Create duplicate orders.
Charge twice unintentionally.
Deduct stock twice.
Redeem a coupon twice.
Consume a reservation twice.
Mark an order as paid more than once.
Use database uniqueness constraints, transactional state transitions, gateway idempotency features where supported, and persisted event records.
Document the order and payment state machine.

17. ORDER DATABASE AND LIFECYCLE
Design a proper database schema. The final schema may differ according to the ORM, but it must support the following concepts.
Core entities
Product.
Category.
Product image.
Product variant.
Inventory record or inventory balance.
Inventory movement, where appropriate.
Combo.
Combo component.
Coupon or discount.
Coupon redemption.
Cart or checkout session, if needed.
Order.
Order item.
Shipping address snapshot.
Payment record.
Payment webhook event.
Inventory reservation.
Administrator.
Administrator session, if persisted.
Shipping configuration.
Refund record.
Audit event.
Do not create redundant tables simply to match this list. Choose a normalized and maintainable design that satisfies the requirements.
17.1 Order snapshots
Orders must preserve historical information.
When an order is placed, store appropriate snapshots of:
Product name.
SKU, if applicable.
Variant weight.
Unit price.
Quantity.
Discount allocation, where applicable.
Shipping charge.
Final total.
Customer contact information.
Shipping address.
Relevant combo composition or item details.
Historical orders must remain understandable even if a product is renamed, its price changes, or it is archived.
17.2 Order statuses
Support an explicit order-fulfillment lifecycle such as:
Pending payment.
Payment verification required.
Confirmed.
Processing.
Packed.
Shipped.
Delivered.
Cancelled.
Manual review or action required.
Keep payment state separate from fulfillment status.
Define permitted transitions and reject invalid transitions server-side.
Do not allow an unpaid order to be marked shipped or delivered without an explicitly supported and audited exception.
Record status history with timestamps and the administrator responsible for each manual change.

18. CUSTOMER ORDER TRACKING
Create a customer-facing /track-order page.
Customers should be able to find their order using:
Order number.
A suitable secure verification method, such as a securely generated tracking token or an additional matching detail.
Prefer a random, high-entropy tracking token delivered through a legitimate post-checkout mechanism over relying solely on a predictable order number.
Because automatic emails and WhatsApp order notifications are not included in this project, design the confirmation page to provide the customer with an appropriate tracking link or token that they can save.
The tracking page should display only the information necessary for the customer to understand their order:
Order number.
Order date.
Fulfillment status.
Relevant item summary.
Shipment or courier name, when entered.
Tracking number, when entered.
Estimated delivery text, when configured.
A clear status timeline.
Do not expose full payment credentials, administrator notes, internal events, or another customer's information.
Rate-limit tracking attempts and prevent order-number enumeration.
Use safe responses for invalid requests so the endpoint does not become a customer-data discovery tool.

19. ADMINISTRATOR AUTHENTICATION
Create a secure administrator login system.
The administrator login should use email and password.
There must be:
No public administrator registration.
No default production password.
No credentials hardcoded in source code.
A documented secure initial-administrator setup procedure.
Strong password hashing using an appropriate modern password-hashing algorithm.
Secure session management.
HTTP-only session cookies.
Secure cookies in production.
Appropriate SameSite configuration.
Session expiration and invalidation.
Login rate limiting.
Server-side authorization on every protected action.
Protection against common authentication attacks.
CSRF protection where applicable to the chosen session and request model.
If a first-admin bootstrap command is implemented, it must be explicitly invoked by the operator, validate input, and avoid printing passwords or storing them in plaintext.
Do not treat hiding an admin link as security.
Do not rely on client-side route protection alone.
Every sensitive database operation must enforce authorization on the server.

20. ADMIN DASHBOARD
Create a premium but practical administration interface.
The admin dashboard should prioritize speed, clarity, accuracy, and operational usefulness rather than decorative animation.
20.1 Dashboard overview
Display database-backed metrics such as:
Total orders.
Verified revenue.
Orders awaiting action.
Orders awaiting payment reconciliation.
Low-stock variants.
Recently placed orders.
Define exactly what each metric means.
Do not count failed payments as revenue. Do not include pending payment amounts in verified revenue.
Do not display invented analytics or placeholder metrics as real data.
Provide helpful empty states for a new store.
20.2 Product management
Administrators must be able to:
View products.
Search products.
Create products.
Edit products.
Archive or deactivate products.
Manage categories.
Manage images.
Manage fixed-weight variants.
Update prices.
Adjust stock.
Mark products as featured.
Manage combo products.
Use appropriate forms, validation, error messages, loading indicators, and save confirmations.
20.3 Order management
Administrators must be able to:
View orders.
Search by order number.
Filter by payment status.
Filter by fulfillment status.
Filter by date range.
Open order details.
Review items and variants.
Review shipping details.
Review payment status.
View relevant payment references.
Update fulfillment status.
Add courier or shipping provider details.
Add a tracking number.
Add internal notes where appropriate.
Record manual refund details.
Review order status history.
Do not permit the administrator to modify historical totals or payment facts casually.
Any sensitive correction should use an explicit, validated, auditable operation.
20.4 Shipping management
Administrators must be able to set:
Shipping provider name.
Tracking number.
Shipment date, if needed.
Delivery estimate text.
Relevant shipment status.
No automatic courier integration is required initially.
20.5 Coupon management
Provide interfaces for:
Creating coupons.
Editing coupons.
Activating and deactivating coupons.
Setting expiry dates.
Setting minimum order values.
Setting usage limits.
Reviewing redemption counts.
20.6 Store settings
Allow the administrator to configure appropriate business settings such as:
Store display name.
Support WhatsApp number.
Contact email.
Shipping rules.
Free-shipping threshold.
Delivery estimate text.
Low-stock threshold defaults.
Optional announcement content.
Optional social links.
Do not allow editing arbitrary application secrets through the public web interface.
Keep sensitive deployment credentials in environment variables or a suitable secret-management system.

21. MANUAL REFUND MANAGEMENT
Refunds are initiated manually through the Razorpay dashboard.
Do not implement automatic refunds through the Razorpay API in this initial project.
The application must allow an administrator to record:
Refund status.
Refund amount.
Date.
Gateway reference, if available.
Reason or internal note.
Administrator who recorded the change.
Possible local statuses include:
Not required.
Pending.
Processed.
Failed.
Do not label a refund as processed until the administrator has verified the corresponding gateway action.
If a refund has been initiated but is not confirmed, represent that distinction accurately.
Define cancellation and restocking rules explicitly.
Ensure restocking can occur only once for a given cancellation or applicable adjustment. Do not automatically restock items simply because a refund record exists.
For uncertain payment or inventory situations, surface an explicit manual-review task.

22. FLOATING WHATSAPP SUPPORT
Add a small floating WhatsApp support control on customer-facing pages.
Requirements:
Use a configurable business phone number.
Do not invent a real number.
Build the WhatsApp destination from validated configuration.
Use a suitable accessible label.
Keep it visually consistent with the brand.
Ensure it does not cover important mobile checkout controls.
Use a sensible mobile position.
Open the official WhatsApp destination safely.
Avoid intrusive pulsing or repeated motion.
Do not create a contact form.
The contact page should display configured business contact information and the WhatsApp support option.

23. STATIC POLICY AND INFORMATION PAGES
Build:
About.
Contact.
Privacy Policy.
Terms and Conditions.
Shipping Policy.
Returns and Refunds Policy.
These pages are static in the initial version and do not need admin editing.
Write clear, readable starter content, but do not invent:
Legal business names.
Registration details.
GST or FSSAI numbers.
Business addresses.
Refund guarantees.
Delivery guarantees.
Product certifications.
Warranty terms.
Statutory claims.
Use obvious placeholders for unknown business details.
Mark legal and policy text that requires owner or professional review before launch.
Ensure the pages reflect the actual operating model: guest checkout, Razorpay payments, manual shipping, no COD, and manually initiated refunds.
Do not promise automatic email notifications or courier tracking integrations that are not implemented.

24. MOTION DESIGN AND ANIMATION SYSTEM
The website should feel alive, refined, and expensive without becoming distracting or slow.
Animation must support the visual story and shopping experience.
24.1 Recommended animation packages
First inspect the repository and existing dependencies.
Prefer Motion for React for most React interface animation work if compatible with the project.
Install only when appropriate:
npm install motion
Use imports from motion/react according to the installed package version and its official documentation.
Use Motion for:
Hero entrance sequences.
Section reveal animations.
Product-card hover interactions.
Navigation transitions.
Dropdowns and mobile menus.
Cart feedback.
Lightweight UI state transitions.
Consider GSAP only when the design genuinely needs more advanced timeline choreography or complex scroll-linked sequences.
If appropriate, install:
npm install gsap @gsap/react
Use GSAP in a limited, deliberate way. Avoid using two animation systems for the same simple task.
Consider Lenis only if a smooth-scrolling experience provides a clear benefit and works correctly with the selected Next.js setup, accessibility preferences, and browser behavior.
If appropriate, install:
npm install lenis
Do not install all animation packages automatically. Select the smallest suitable dependency set after checking the repository, compatibility, bundle impact, and current official documentation.
If native CSS animations or transitions are sufficient, use them.
24.2 Hero animation
Create a refined entrance sequence:
Headline reveals with restrained motion.
Supporting copy appears naturally.
Calls to action enter without excessive delay.
Product imagery receives a subtle entrance or reveal.
Decorative gold details move only if they contribute to the composition.
Avoid a long sequence that prevents shoppers from interacting.
24.3 Scroll reveals
Use subtle, staggered reveals for selected homepage sections.
Requirements:
Animate only when helpful.
Avoid excessive movement.
Prevent layout shifts.
Do not hide essential content permanently if JavaScript fails.
Avoid repeatedly replaying animations in ways that distract from browsing.
24.4 Product interactions
Add polished interactions such as:
Gentle image scaling on hover.
Subtle image transitions.
Clear button hover and pressed states.
Smooth variant selection feedback.
Cart confirmation feedback.
Consistent focus states.
Do not animate prices in a way that obscures the actual amount.
24.5 Navigation and cart
Add restrained transitions to:
Mobile navigation.
Search panels.
Cart drawers, if implemented.
Dropdowns.
Toasts and inline feedback.
Make sure focus moves correctly when opening or closing overlays.
24.6 Performance and accessibility
Respect prefers-reduced-motion.
Provide nonanimated fallbacks.
Avoid unnecessary scroll listeners.
Avoid expensive full-page blur or particle effects.
Animate transforms and opacity where appropriate.
Avoid blocking the main thread.
Keep animation libraries out of client bundles where they are not needed.
Do not make scrolling feel broken.
Do not sacrifice usability for animation.
The result should feel cinematic in the hero, polished in the product experience, and calm during checkout.

25. RESPONSIVE DESIGN
Design mobile-first, then refine for tablet and desktop.
Test at a range of viewport sizes, including approximately:
320–375 px mobile.
390–430 px mobile.
768 px tablet.
1024 px small desktop.
1280–1440 px desktop.
Wider desktop screens.
These are test targets, not rigid breakpoints.
Mobile requirements
Compact, readable header.
Functional navigation menu.
Excellent hero composition.
Product images with sensible aspect ratios.
Touch-friendly buttons.
Clear variant selection.
Easy quantity adjustment.
Readable prices.
Simple checkout form.
No horizontal overflow.
No tiny or overlapping controls.
WhatsApp button that does not obstruct checkout.
Clear cart and order-tracking interfaces.
Desktop requirements
Deliberate content widths.
Balanced whitespace.
Well-composed imagery.
Clear shopping hierarchy.
Comfortable product grid density.
Refined navigation.
Well-structured admin tables and forms.
Do not simply shrink the desktop design. Make deliberate responsive decisions for each layout.

26. ACCESSIBILITY
Build accessible interfaces from the beginning.
Implement:
Semantic landmarks.
Logical heading hierarchy.
Proper labels for every form control.
Keyboard-operable interactions.
Visible focus indicators.
Accessible dialogs and menus.
Appropriate accessible names for icon-only buttons.
Useful alternative text for meaningful product imagery.
Empty alt text for purely decorative imagery.
Sufficient text contrast.
Accessible validation and error messages.
Status updates that can be announced when appropriate.
Reduced-motion support.
Correct focus management for overlays.
No reliance on color alone to communicate status.
Use automated accessibility checks where practical and manually inspect important flows.
Do not claim complete WCAG compliance without sufficient evaluation.

27. SEO AND DISCOVERABILITY
Implement sensible technical and on-page SEO.
Include:
Page-specific titles.
Meta descriptions.
Canonical URLs where appropriate.
Open Graph metadata.
Appropriate social-sharing metadata.
Clean, stable product slugs.
Logical headings.
Internal links.
Search-engine-friendly product pages.
Sitemap generation.
Robots configuration.
Appropriate structured data when supported by accurate information.
Correct handling of archived or unavailable products.
Do not generate fake ratings, review structured data, availability claims, price claims, or business details.
Product structured data must match the visible product information and the actual selected or represented variant.
Avoid indexing administrative pages, internal API endpoints, and private customer tracking data.
Use a configurable production base URL rather than hardcoding a development domain.

28. PERFORMANCE OPTIMIZATION
The website must feel fast on realistic mobile devices and network connections.
Implement:
Optimized product images.
Responsive image sizing.
Lazy loading below the fold.
Appropriate eager loading for the primary hero image.
Avoidance of layout shifts.
Sensible font loading.
Server rendering or static rendering where appropriate.
Efficient database queries.
Pagination or bounded result sets.
Caching only where correctness and data freshness permit.
Small client-side bundles.
Minimal unnecessary dependencies.
Error boundaries or suitable failure handling.
Avoidance of large, unnecessary animation effects.
Do not cache private order details or administrator data publicly.
Do not use stale stock or payment information to authorize a purchase.
Measure performance using available tools when practical. Record the actual results rather than inventing Lighthouse scores.

29. SECURITY REQUIREMENTS
Treat security as a core requirement, not a final cosmetic task.
Implement appropriate protection against:
SQL injection.
Cross-site scripting.
Cross-site request forgery where applicable.
Broken access control.
Insecure direct object references.
Unauthorized product or price manipulation.
Fake payment success.
Duplicate payment callbacks.
Duplicate order creation.
Inventory overselling.
Coupon abuse.
Brute-force administrator login attempts.
Customer tracking enumeration.
Unsafe file uploads.
Exposed environment variables.
Sensitive information in logs.
Excessive request rates.
Unsafe redirects.
Improper error disclosure.
Use:
Server-side validation.
Parameterized database access.
Strong authorization checks.
Secure session handling.
Rate limiting on sensitive endpoints.
Appropriate response security headers.
Safe file upload validation.
Payment signature verification.
Webhook signature verification.
Idempotent payment and order processing.
Database constraints and transactions.
Restricted CORS settings where relevant.
Safe logging and error handling.
Do not rely on browser validation for security.
Do not trust hidden fields, client totals, local storage, client-supplied order status, or browser redirect parameters.
Do not expose stack traces, secret values, private customer details, or raw database errors to visitors.
Document any security limitation that cannot be resolved without an external service or configuration.

30. DATABASE DESIGN AND MIGRATIONS
Create a maintainable PostgreSQL schema and migration system.
The schema must support the complete catalog, variants, stock, combos, discounts, guest orders, payments, inventory reservations, admin access, shipment tracking, and refund records.
Requirements:
Stable primary keys.
Foreign-key relationships.
Unique constraints where required.
Appropriate indexes.
Monetary values represented safely.
Timestamps stored consistently.
Explicit status values.
Database-level integrity rules where possible.
Transactions for important multi-record changes.
Safe migration practices.
A documented seed process.
Do not use an in-memory database for production business records.
Do not silently delete historical orders when removing products.
Do not store a mutable copy of a product price as the sole source of truth for future purchases; use the database for current catalog pricing and order snapshots for historical transactions.
Provide a migration command and a documented setup sequence.
If a development seed creates sample products or orders, label them as demo data. Never mix fake revenue or sample customer data into production analytics.

31. API AND SERVER ACTION DESIGN
Design the application's API or server actions around clear responsibilities.
Implement the operations needed for:
Product browsing.
Category browsing.
Product search.
Product details.
Cart validation.
Shipping calculation.
Coupon validation.
Checkout creation.
Inventory reservation.
Razorpay order creation.
Payment verification.
Webhook processing.
Order confirmation.
Customer tracking.
Administrator authentication.
Product CRUD.
Image management.
Variant management.
Inventory adjustment.
Coupon CRUD.
Order search and management.
Shipping updates.
Manual refund recording.
Store settings.
These are logical capabilities, not a mandate to create one endpoint per bullet.
For every privileged operation:
Validate inputs.
Authenticate the caller when required.
Authorize the action.
Perform the operation on the server.
Handle errors safely.
Return only the information the caller is allowed to see.
Use transactions where multiple records must change together.
Do not expose database models or secrets unnecessarily.
Use consistent error shapes and appropriate HTTP status codes.

32. ENVIRONMENT VARIABLES AND CONFIGURATION
Create .env.example with clear placeholders for every required configuration value.
Include relevant variables for:
PostgreSQL connection.
Application base URL.
Administrator bootstrap configuration, if needed.
Razorpay key ID.
Razorpay secret key.
Razorpay webhook secret.
Image-storage account and bucket configuration.
Image-storage access credentials.
Public image delivery base URL, if applicable.
WhatsApp support number.
Business contact email.
Any other genuinely required integration.
Use the naming conventions of the chosen libraries and deployment platform.
Do not include actual credentials.
Document which variables are public and which must remain server-only.
Validate required configuration at startup or at the relevant operation, with useful error messages that do not expose secret values.
Avoid making the entire storefront fail unnecessarily just because an optional integration has not been configured during local development.
Payment creation and production order processing must fail safely when required payment configuration is absent.

33. CLOUDFARE DEPLOYMENT
Prepare the application for the intended initial Cloudflare Workers deployment.
Before selecting the deployment architecture:
Inspect the current Next.js version and features in use.
Verify the currently supported Cloudflare deployment approach from official documentation.
Confirm compatibility for the chosen routing, server rendering, image handling, database access, and required Node.js APIs.
Select an appropriate compatible adapter or deployment framework.
Verify environment-variable and secret configuration.
Document the actual build and deployment process.
Do not assume every standard Next.js feature works identically in every Cloudflare runtime.
Do not assume a local filesystem persists between requests.
Use persistent object storage for product images.
Use an external PostgreSQL provider with a connection strategy compatible with the selected runtime.
Evaluate:
Free-tier limits.
Connection pooling or HTTP-compatible database access.
Database sleeping or expiration.
Backup availability.
Migration access.
Rate limits.
Likely production costs.
Recovery options.
Do not force a specific provider if a better-compatible option exists. Explain the selected provider and why it fits the current architecture.
The deployment guide must include:
Required accounts and services.
Required configuration variables.
Database provisioning.
Database migrations.
Initial administrator creation.
Image-storage setup.
Razorpay test configuration.
Local development instructions.
Production build.
Deployment commands.
Custom-domain setup when a domain is available.
Troubleshooting.
Backup and recovery recommendations.
A provider's free tier may change. Document the current assumptions and advise checking the provider's latest terms.

34. ERROR HANDLING AND OPERATIONAL RELIABILITY
Handle failures deliberately.
Provide user-friendly messages for:
Product not found.
Variant unavailable.
Out-of-stock item.
Price changed during checkout.
Invalid coupon.
Expired coupon.
Shipping calculation failure.
Payment initialization failure.
Payment failure.
Payment still processing.
Payment verification failure.
Duplicate callback.
Reservation expiry.
Uncertain payment state.
Order tracking failure.
Image upload failure.
Database unavailable.
Administrator session expiry.
Do not show a generic success message after an operation fails.
Do not lose a potentially successful payment because a browser request timed out.
Use explicit pending and reconciliation states where required.
Log useful operational events without recording passwords, payment secrets, sensitive payment data, or unnecessary personal information.
Provide appropriate administrator-visible alerts for orders requiring manual intervention.

35. TESTING STRATEGY
Write tests for the important business rules, not just component rendering.
Use the testing framework already present where practical. If no suitable framework exists, choose one compatible with the stack.
35.1 Unit tests
Test:
Variant pricing.
INR monetary calculations.
Shipping rules.
Free-shipping threshold.
Coupon eligibility.
Discount limits.
Quantity validation.
Inventory calculations.
Combo component requirements.
Order state transitions.
Payment state transitions.
Refund-record rules.
35.2 Integration tests
Test:
Product retrieval.
Variant availability.
Cart validation.
Checkout creation.
Inventory reservation.
Reservation expiration.
Payment verification.
Webhook verification.
Idempotent payment processing.
Duplicate webhook delivery.
Coupon redemption limits.
Order creation and confirmation.
Customer tracking authorization.
Administrator authorization.
Manual refund recording.
Inventory restoration on applicable cancellations.
35.3 End-to-end tests
Test the primary shopper journey:
Open the homepage.
Browse a category.
Open a product.
Select a pack size.
Add the product to the cart.
Change quantity.
Apply an eligible coupon.
Enter shipping details.
Initiate Razorpay test checkout.
Complete a test payment.
Confirm the resulting order.
Open the tracking page.
Verify the order status.
Also test unsuccessful and interrupted journeys.
35.4 Critical concurrency tests
Test:
Two customers attempting to buy the final unit.
Duplicate checkout requests.
Duplicate payment callbacks.
Webhook arriving before a browser verification request.
Browser verification arriving before the webhook.
Reservation expiry during payment.
Late payment after stock is reallocated.
Duplicate coupon redemption attempts.
Concurrent cancellation and payment events.
The expected result is a consistent order and inventory state without overselling or duplicate deductions.
35.5 Security tests
Test:
Unauthenticated admin access.
Unauthorized product changes.
Unauthorized order status changes.
Malformed checkout data.
Forged payment verification data.
Invalid webhook signatures.
Replayed webhook events.
Tracking attempts against other orders.
Invalid upload types.
Oversized uploads.
Excessive login attempts.
35.6 Responsive and accessibility checks
Check:
Mobile navigation.
Product grid.
Variant selection.
Cart.
Checkout.
Order confirmation.
Tracking page.
Administrator forms.
Keyboard navigation.
Reduced-motion behavior.
Contrast and focus states.
35.7 Test reporting
Run the checks that the environment supports.
Report:
Tests run.
Tests passed.
Tests failed.
Tests not run.
External credentials or services still required.
Any known limitations.
Do not claim live Razorpay payments, real email delivery, real courier integration, or production deployment have been tested unless those activities actually occurred.

36. CONTENT AND PRODUCT DATA
The store must feel authentic, but authenticity must not be manufactured through false claims.
Use concise, natural copy.
Avoid:
Repetitive AI-style headlines.
Overused luxury buzzwords.
Exaggerated health claims.
Fake urgency.
Fake stock scarcity.
Fake customer reviews.
Fake ratings.
Invented customer testimonials.
Invented certifications.
Unverified nutritional facts.
Made-up sourcing stories.
Create a small development seed catalog only if useful for testing.
Label all sample data as development or demo data.
Use believable product names and image placeholders, but do not present placeholder pricing, nutrition, origin, or certifications as verified business information.
Ensure the administrator can replace demo content with actual business data.
Keep product descriptions short enough to support a visually focused storefront, with more detail available on the product page.

37. UI COMPONENT SYSTEM
Create reusable components for genuinely repeated interface patterns.
Possible components include:
SiteHeader.
AnnouncementBar.
MobileNavigation.
SiteFooter.
ProductCard.
ProductGrid.
CategoryTile.
ProductGallery.
VariantSelector.
QuantitySelector.
PriceDisplay.
AddToCartButton.
CartSummary.
CartItem.
CouponInput.
ShippingForm.
CheckoutSummary.
PaymentStatus.
OrderTimeline.
WhatsAppSupportButton.
AdminSidebar.
AdminHeader.
AdminDataTable.
AdminFormField.
ImageUploader.
StatusBadge.
EmptyState.
LoadingState.
ErrorState.
ConfirmationDialog.
Toast or notification component.
Use this list as guidance, not as a requirement to create redundant abstractions.
Keep design tokens centralized for:
Colors.
Typography.
Spacing.
Shadows.
Border treatments.
Animation durations.
Breakpoints where appropriate.
Avoid a situation where every page has a different button, spacing rule, or color interpretation.
The admin interface should share brand identity but remain optimized for efficient business operations.

38. DESIGN QUALITY CONTROL
After implementing the site, inspect the actual rendered application at available viewport sizes.
Do not assume that code is visually correct simply because it compiles.
Review:
Hero composition.
Logo sizing.
Headline wrapping.
Image crop.
Product card alignment.
Price visibility.
Spacing rhythm.
Section transitions.
Responsive behavior.
Navigation alignment.
Checkout readability.
Admin table usability.
Loading and error states.
Animation timing.
Reduced-motion behavior.
Fix obvious layout defects, overflow, broken images, inconsistent spacing, weak contrast, and awkward mobile layouts.
Prefer meaningful design improvements over adding more effects.
If browser automation or screenshots are available in the environment, use them to inspect the result. If visual inspection is unavailable, clearly report that limitation.

39. IMPLEMENTATION ORDER
Implement the project in a sequence that minimizes rework while delivering a complete result.
Phase 1 — Repository and architecture
Inspect the repository.
Locate and verify the logo.
Inspect current dependencies.
Establish the route structure.
Confirm the compatible deployment approach.
Define the database and environment configuration.
Phase 2 — Foundation
Set up or refine the application shell.
Define design tokens.
Implement typography.
Implement shared navigation and footer.
Configure image handling.
Establish form validation and error patterns.
Phase 3 — Database and core domain
Create schema and migrations.
Implement products and categories.
Implement variants and inventory.
Implement combos.
Implement shipping rules and discounts.
Create development seed data where appropriate.
Phase 4 — Premium storefront
Build the homepage.
Build categories and catalog.
Build search.
Build product detail pages.
Implement product image galleries.
Implement responsive product cards.
Implement carefully chosen animations.
Phase 5 — Cart and checkout
Implement guest cart persistence.
Implement server-side cart validation.
Implement coupon validation.
Implement shipping calculations.
Implement checkout form.
Implement inventory reservations.
Phase 6 — Payments and orders
Integrate Razorpay.
Implement payment verification.
Implement webhook processing.
Implement idempotent state transitions.
Implement reservation expiration and reconciliation.
Implement order confirmation.
Phase 7 — Administrator dashboard
Implement admin bootstrap and authentication.
Implement secure sessions.
Implement product management.
Implement image uploads.
Implement variants and inventory.
Implement combos and coupons.
Implement order management.
Implement shipping updates.
Implement manual refund recording.
Implement store settings and dashboard metrics.
Phase 8 — Customer-facing operational pages
Implement order tracking.
Implement contact and WhatsApp support.
Implement static policy pages.
Implement error and empty states.
Phase 9 — Quality and hardening
Run type checking.
Run linting.
Run tests.
Test payment edge cases.
Test concurrency and stock rules.
Check accessibility.
Check responsive layouts.
Optimize images and bundles.
Review security and configuration.
Phase 10 — Deployment readiness
Verify deployment compatibility.
Document environment variables.
Document database provisioning.
Document storage setup.
Document admin bootstrap.
Document Razorpay test mode.
Document deployment commands.
Document backup and recovery.
Produce a final completion report.
Do not stop after the homepage. Continue until the core application has been implemented and verified as far as the environment permits.

40. REQUIRED PROJECT DOCUMENTATION
Create or update the README with:
Project overview.
Main features.
Technology stack.
Prerequisites.
Installation instructions.
Local development commands.
Environment variable setup.
Database setup.
Migration commands.
Development seed commands.
Administrator bootstrap.
Product image storage setup.
Razorpay test-mode setup.
Running tests.
Production build.
Cloudflare deployment.
Troubleshooting.
Security considerations.
Backup and recovery.
Known limitations.
Include .env.example.
Include any required deployment configuration.
Include a clear list of dependencies and why each significant new dependency is required.
Do not include actual secrets in the README or repository.

41. ACCEPTANCE CRITERIA
The project is not considered complete simply because the homepage renders.
The following conditions define the expected result.
Customer experience
Homepage displays the original brand logo.
Homepage matches the agreed premium emerald, gold, and ivory art direction.
Navigation works.
Product categories work.
Product search works.
Product pages load real product data.
Fixed-weight variants display the correct prices.
Out-of-stock products cannot be purchased.
Product images display correctly.
Cart persists across ordinary navigation and refreshes.
Cart totals are revalidated on the server.
Discounts work according to their rules.
Shipping is calculated correctly.
Guest checkout validates customer information.
Razorpay checkout can be configured in test mode.
Payment success is verified server-side.
Payment failure is handled safely.
Successful orders show an appropriate confirmation.
Customers can access secure order tracking.
The WhatsApp support control uses configured contact details.
Policy pages are available.
The interface works on mobile and desktop.
Administrator experience
Administrator login is secure.
Public administrator registration is disabled.
Unauthorized visitors cannot access admin operations.
Products can be created and edited.
Product images can be uploaded and reordered.
Fixed-weight variants can be managed.
Stock can be managed.
Combo products can be configured.
Coupons can be managed.
Orders can be searched and filtered.
Order fulfillment status can be updated safely.
Shipping details can be recorded.
Refund actions can be recorded manually.
Dashboard metrics reflect actual database data.
Business correctness
Inventory cannot be oversold through ordinary concurrent checkout.
Combo component stock is handled correctly.
Payment callbacks are idempotent.
Duplicate payment events cannot double-deduct inventory.
Late payments have an explicit reconciliation path.
Coupons respect usage limits.
Historical orders retain accurate snapshots.
Unpaid orders are not incorrectly treated as revenue.
Customer tracking does not expose other customers' data.
Administrative changes are authorized and appropriately audited.
Engineering quality
Database migrations exist.
Required configuration is documented.
Secrets are not exposed.
The application builds in the supported environment.
Type checking and linting are run where available.
Important business logic has tests.
Known failures and untested features are reported honestly.
Deployment instructions are practical and consistent with the selected stack.

42. FINAL EXECUTION INSTRUCTIONS
Now begin.
Inspect the existing repository and available brand assets.
Summarize the discovered architecture briefly.
Identify any critical compatibility constraints.
Choose the most appropriate implementation approach.
Start implementing the application directly.
Install only the necessary, compatible packages.
Build the storefront and backend as one coherent product.
Run checks regularly and fix issues you discover.
Test important business rules and failure paths.
Verify the actual rendered UI wherever tools permit.
Document any configuration I must supply.
Provide a final report with implemented features, files or major modules created, commands run, test results, deployment steps, and remaining blockers.
Do not return only a plan, a tutorial, a checklist, or a collection of code snippets. Implement the solution in the repository.
Do not stop after building the visual design. Complete the database, inventory, checkout, payment verification, administration, order lifecycle, and deployment preparation.
Do not fake functionality to make the demo look complete.
Do not introduce extra features that distract from the agreed business scope.
If a task cannot be completed because an external credential, account, service, or unavailable tool is required, implement the safe integration boundary, explain the exact missing requirement, and continue with the remaining work.
Final quality standard: Build SVG Dry Fruits as a distinctive, premium, trustworthy Indian ecommerce brand with a beautiful human-crafted interface, reliable business logic, secure payment handling, accurate inventory, a practical admin dashboard, and a clear path to deployment. Every visual choice must serve the brand; every interaction must work; every financial and inventory operation must be handled carefully; and every claim about completion must be truthful.

