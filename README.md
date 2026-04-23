# NullNet-Script-V2.0

NullNet is a multi-role PHP marketplace for buying and selling digital goods (RDPs, cPanels, Shells, Mailers, SMTPs, Leads, Premium Accounts, Banks, Scampages, Tutorials). It bundles a buyer storefront, a seller back-office, an admin/staff control panel and a built-in support/ticketing system.

This README documents **every feature** currently shipping in the script.

---

## Table of contents

1. [Tech stack](#1-tech-stack)
2. [Roles and authentication](#2-roles-and-authentication)
3. [Buyer features](#3-buyer-features)
4. [Seller features](#4-seller-features)
5. [Admin panel features](#5-admin-panel-features)
6. [Support panel features](#6-support-panel-features)
7. [Payments & financial pipeline](#7-payments--financial-pipeline)
8. [Reports, refunds & disputes](#8-reports-refunds--disputes)
9. [Tickets & messaging](#9-tickets--messaging)
10. [Product catalog](#10-product-catalog)
11. [Database tables](#11-database-tables)
12. [File / directory layout](#12-file--directory-layout)

---

## 1. Tech stack

- **Language**: PHP 7+ (procedural, `mysqli`, no framework)
- **Database**: MySQL / MariaDB (`includes/config.php` builds `$dbcon`)
- **Web server**: Apache (started by `start.sh`)
- **Frontend**: Bootstrap 3, jQuery, Bootbox, SweetAlert, DataTables, CKEditor
- **External APIs**: Block.io (BTC payouts), Blockchain.info (BTC/USD rate), BitPay rates, Etherscan / blockchain.com (TX explorers), jsdelivr countries-states-cities JSON

---

## 2. Roles and authentication

| Role     | Login page             | Session key             |
|----------|------------------------|-------------------------|
| Buyer    | `/login.php`           | `$_SESSION['uname']`    |
| Seller   | `/seller/login.php`    | `$_SESSION['sname']`    |
| Admin    | `/admin/login.php`     | `$_SESSION['aname']`    |
| Support  | `/support/login.php`   | `$_SESSION['supname']`  |

Account features:
- Sign up (`signup.php`, `signupform.php`) with email verification (`check.php`, `checkEmailChange.php`).
- Forgot / reset password flow (`forget.php`, `resetpass.php`, `passform.php`).
- Email change with confirmation token.
- "Become a seller" upgrade (`becomeseller.php`) – flips `users.resseller=1` and grants access to `/seller/`.
- Settings page (`setting.php`, `settingEdit.php`) for profile / password / email updates.
- Logout (`logout.php`).
- Login form variants (`loginform.php`, `loginpage1/2/3.php`) and the unified `login.php` (with the **REGISTER** button linking to signup).

---

## 3. Buyer features

### Storefront browsing
- Categorised storefront pages (`divPage1.php` … `divPage15.php`) for each product type with filters, search, and per-row buy buttons.
- Mirror pages under `buyer/` for legacy entry points.
- Detail / preview pages with item info, price, country flags, sample data and seller name.
- Cascading country/state selectors on RDP listings (jsdelivr JSON dataset, cached).

### Buying flow
- One-click **Buy** with `bootbox.confirm` confirmation modal.
- Per-category buy endpoints (`buytool.php`, `buyscam.php`, `buytuto.php`, `buy*.php`) returning explicit JSON statuses (`success`, `sold`, `nobalance`, `error`).
- Success modal with order #, item name, price, and a **My Orders** jump button.
- Animated removal of the purchased row from listings.
- Balance-based purchases (no checkout flow – buyer pre-tops their balance).

### Wallet / balance
- BTC and Ethereum balance top-ups via `payment.php` (PMcheck, addBalance, addBalanceAction).
- Live BTC/ETH rate display.
- Pending top-up requests visible until an admin approves them.
- Combined balance displayed in the buyer header.

### My orders / proofs
- View purchased items (`openorder.php`, `orders.php`, `showorder.php`) with download / reveal of sensitive data once paid.
- View seller-supplied proof image (`showProof.php`) for tools/tutorials/scampages.
- Report a bad purchase to open a dispute (see §8).

### Tickets
- Open a support ticket (`openticket.php`, `tticket.php`, `tickets.php`, `showTicket.php`).
- Threaded replies (`addReply.php`).

### Tutorials & extras
- Buy / read tutorials, premium accounts, banks, scampages.
- Browse static informational pages and `tutorial.php`.

---

## 4. Seller features

Seller back-office lives under `/seller/` and is gated by `$_SESSION['sname']`.

### Dashboard
- Welcome dashboard with sales stats (sold count, unsold, profit, lastweek summary).

### Listings management
- Add / edit / delete listings for every supported product type:
  - RDPs (with cascading Country / State dropdowns + proof image upload via `seller/rdpTab2.php`, `seller/rdpAdd.php`).
  - cPanels, Shells, Mailers, SMTPs, Leads, Premium Accounts, Banks, Scampages, Tutorials.
- Per-product unsold inventory counts and quick edit.

### Sales & profit hold
- `/seller/sales.php` shows every sale with status badges (Pending Hold, Released, Approved, Refunded), live JS countdown for the 10-hour hold window, and live totals.
- Auto-release sweep when the seller opens Sales or Withdrawal.

### Withdrawal (`/seller/withdrawal.php`)
- Three-tab UI: **Sales**, **Edit Address**, **Payment History**.
- Sales tab shows: Total Sales, Pending Hold (count + USD), Released (= `soldb`), Share %, You Receive (65%).
- BTC / ETH method selector – button stays disabled until that method's address is set.
- Independent BTC and ETH address editors (`seller/ajax/updatebtc.php`, `seller/ajax/updateeth.php`).
- Payment History tab pulls past payouts from `rpayment`.
- Status banners for `requested` (awaiting admin) and **`rejected`** (shows the admin's rejection note and re-enables the request form).

### Disputes from the seller side
- `/seller/refund.php` – seller can refund a reported purchase from the report ticket itself (Refund button inside `seller/vr.php`). Refund logic respects the profit-hold ledger and **clamps `soldb` and `isold` at 0** so a seller can never go into a negative balance.
- View buyer reports against your listings (`seller/reports.php` → `seller/vr.php`, threaded replies).

### Tickets
- Seller can read and respond to staff/admin tickets (`tticket.php`, `addReply.php`).

---

## 5. Admin panel features

`/admin/` – gated by `$_SESSION['aname']` and `users.role='admin'`.

### Sidebar / nav (`admin/header.php`)
- Live count badges for Tickets, Reports, and combined Payments.

### Users management
- `users.php`, `usersb.php`, `userss.php`, `user.php`, `userhistory.php`:
  - List, search, ban/unban, edit balance, view full user history (purchases, top-ups, reports, tickets).
  - Per-user IP, registration date, last login, total purchases.

### Vendors / sellers
- `vendors/`, `resseller.php`, `ress.php`, `viewr.php` – manage seller accounts, view their per-category sales totals.
- Promote / demote sellers.

### Listings moderation
- `toolsvis.php` – toggle visibility of any listing.
- `remover.php` – remove inappropriate listings.
- Per-category admin views.

### Payments hub (`/admin/payments.php`)
A unified four-tab interface with a **search bar** that filters every table on the page (seller, buyer, email, method, address, status, amount, txid, order # …).

1. **Withdraw Approval**
   - Pending seller payout requests with: released $, pending hold, reported amount, receive USD/BTC, method (BTC/ETH), payout address, NullNet 35% cut.
   - **Pay** button — instantly approves the withdrawal, records a successful payout in `rpayment`, resets seller's released balance, and removes the row.
   - **Reject** button — opens a textarea modal where the admin types a required rejection note. The seller sees the note on their withdrawal page and can submit again.
2. **Payment Approval** – Buyer balance top-up requests (BTC / ETH). Approve credits `users.balance`; Reject denies. Confirmation modals via Bootbox; live count badges and table refresh.
3. **Seller Profit Hold** – every pending `seller_payments` row with a live JS countdown and **Approve Now** to release immediately (skips the 10-hour wait). Reported items stay frozen.
4. **All Payments History** – combined chronological feed of balance top-ups, purchases, and seller-profit credits with status badges.

Backend endpoints used:
- `admin/withdrawAction.php` — pay / reject withdrawal requests.
- `admin/paymentapproval.php` — approve / reject buyer top-ups (also exposes a JSON fragment endpoint for live refresh).
- `admin/approvepayment.php` — early-release a held seller payment.
- `admin/rpay.php` — legacy block.io BTC payout form (still on disk).

### Reports / refunds
- `admin/reports.php`, `admin/refundr.php`, `admin/closereport.php` – manage buyer↔seller disputes, issue refunds (profit-hold aware – cancels pending entries instead of double-debiting), close reports.
- `admin/treport.php`, `admin/showReport.php`, `admin/addReportReply.php` – threaded report messaging.

### Tickets
- `admin/tickets.php`, `openticket.php`, `closeticket.php`, `viewt.php` – staff ticket queue, assignment, threaded replies.
- `admin/assign.php`, `admin/assignedtome.php` – ticket assignment tools.

### Sales / news / news flash
- `admin/sales.php` – global sales analytics.
- `admin/news.php`, `admin/mnews.php` – publish marketplace news / announcements.

### Site settings
- `admin/dollar.php` – set the USD/BTC reference rate.
- `admin/cr.php` – CKEditor content editor.
- `admin/css/`, `admin/stylesheets/` – theming.

### Cron-style sweeps
- `admin/payments.php` triggers the auto-release of held seller payments past the 10-hour window with no report.

---

## 6. Support panel features

`/support/` – staff agents with a narrower scope than admins.

- Login (`support/login.php`), logout.
- Tickets queue with assign / take ownership.
- Reports queue with limited refund powers (`support/refundr.php`).
- Threaded messages on tickets and reports.

---

## 7. Payments & financial pipeline

### Currencies
- Internal balance is in USD.
- Top-ups accepted in **BTC** and **Ethereum**; payouts in **BTC** or **ETH** (seller-chosen).
- Live rate from blockchain.info, BitPay, or admin-set rate (`admin/dollar.php`).

### 10-hour seller profit hold (core)
1. **At purchase**: insert into `purchases`, insert into `seller_payments` with `status='pending'` and `release_date = NOW() + 10h`. **No** immediate credit to `resseller.soldb`. Buyer balance debited.
2. **Auto-release sweep**: runs whenever a seller opens `/seller/sales.php` or `/seller/withdrawal.php`, or an admin opens `/admin/payments.php`. Released only if no report exists.
3. **Early approval**: admin can hit **Approve Now** (handled by `admin/approvepayment.php`).
4. **Refund-aware**: refund logic checks `seller_payments` first — pending → mark refunded, no `soldb` change; released → subtract from `soldb`.

### Top-up approval
- Users submit a top-up with TX hash + note; admin approves to credit balance, or rejects.

### Seller withdrawal lifecycle
- `withdrawal='requested'` → admin **Pay** sets `withdrawal='done'`, resets `soldb`, records into `rpayment`.
- Admin **Reject** sets `withdrawal='rejected'` and stores the note in `withdraw_note`. Seller sees the note and can request again.
- **Negative-balance protection**: `seller/withdrawal.php` self-heals any negative `soldb`/`isold` to 0 on page load and the POST handler refuses to create a new request unless released balance is positive **and** the 65 % receive amount is at least $10. All three refund paths (`seller/refund.php`, `admin/refundr.php`, `support/refundr.php`) use `GREATEST(... , 0)` when subtracting from `soldb`/`isold` so a refund can never push a seller into the negative.

---

## 8. Reports, refunds & disputes

- Buyer can report any purchase from their orders page (`treport.php`, `showorder.php`).
- Reports are threaded (`addReportReply.php`, `showReport.php`).
- Reported sales freeze the matching `seller_payments` row indefinitely until resolved.
- Admin or Support can:
  - Issue a full refund to the buyer (credits `users.balance`).
  - Cancel the seller's pending payment (no double-debit if still pending).
  - Subtract from `soldb` if the funds were already released.
  - Close the report.
- Refund history stored in `refund` table (auto-created on startup by `start.sh` if missing).
- The seller can also self-refund directly from a report ticket (`seller/vr.php` → Refund modal → `seller/refund.php`).

---

## 9. Tickets & messaging

- Open a ticket (`openticket.php`, `tticket.php`).
- Categorised, with attachments where supported.
- Staff queue with assign / take.
- Threaded replies (`addReply.php`).
- Live count badges in admin/support sidebars.
- Auto-close tools (`closeticket.php`).
- Email notifications via `mailer.php`, `SMTPSend.php`, `smtp.php`, `check2mailer.php`.

---

## 10. Product catalog

Each category has its own table, listing form, buy endpoint, and detail page:

| Category        | Listing form (seller)              | Buy endpoint   | Detail page          |
|-----------------|------------------------------------|----------------|----------------------|
| RDP             | `seller/rdpTab2.php`+`rdpAdd.php`  | `buytool.php`  | `divPage1.php`       |
| cPanel          | seller cPanel form                 | `buytool.php`  | `divPage2.php`       |
| Shell           | seller Shell form                  | `buytool.php`  | `divPage3.php`       |
| Mailer          | seller Mailer form                 | `buytool.php`  | `divPage4.php`       |
| SMTP            | seller SMTP form                   | `buytool.php`  | `divPage5.php`       |
| Leads           | seller Leads form                  | `buytool.php`  | `divPage6.php`       |
| Premium acct.   | seller Premium form                | `buytool.php`  | `divPage7.php`       |
| Banks           | seller Banks form                  | `buytool.php`  | `divPage8.php`       |
| Scampage        | seller Scampage form               | `buyscam.php`  | `divPage9.php`       |
| Tutorial        | seller Tutorial form               | `buytuto.php`  | `divPage10.php`      |

Per-category visibility, removal, and reporting are supported across all categories.

---

## 11. Database tables

Key tables (others exist for per-category storage and admin features):

- `users` — buyer/seller accounts (`balance`, `resseller`, `email`, `role`, `ipurchassed`, …).
- `resseller` — per-seller record:
  - `soldb` (released/withdrawable USD), `unsoldb`, `isold`, `iunsold`,
  - `withdrawal` (`''` | `requested` | `done` | `rejected`),
  - `withdraw_method` (`btc` | `eth`), `btc`, `eth` (payout addresses),
  - `withdraw_note` (admin's last rejection reason),
  - `allsales`, `lastweek`.
- `purchases` — every buyer purchase (`id`, `username`/`buyer`, `resseller`, `acctype`/`type`, `s_id`, `s_url`/`url`, `price`, `date`, `reported`).
- `seller_payments` — profit-hold ledger (`id`, `seller`, `purchase_id`, `amount`, `status`, `release_date`, `released_at`, `approved_at`, `approved_by`).
  - `status` ∈ `pending` | `released` | `approved` | `refunded`.
- `payment` — buyer balance top-up requests (`amountusd`, `method`, `tx_hash`, `note`, `state`).
- `rpayment` — completed seller payouts (`amount`, `abtc`, `adbtc`, `method`, `url` …).
- `reports` — buyer↔seller disputes.
- `refund` — refund history.
- `tickets`, `ticket_replies` — support ticketing.
- Per-product tables: `rdp`, `cpanel`, `shell`, `mailer`, `smtp`, `leads`, `premiumaccount`, `banks`, `scampage`, `tutorial`.

---

## 12. File / directory layout

```
/                   – buyer storefront (login, signup, divPage*.php, buy*.php …)
admin/              – admin/staff control panel (payments hub, users, products, reports …)
seller/             – seller back-office (RDP/Shell/Mailer add forms, sales, withdrawal …)
support/            – staff support agents (tickets, refunds with limited scope)
buyer/              – legacy buyer subsite (mirrors of root pages)
includes/           – shared `config.php` (DB), `header.php`, common helpers, `block_io.php`
files/              – uploaded assets (proof images, ID docs, etc.)
_lib/, vendor/      – third-party JS/CSS libs
start.sh            – Apache launcher used by the Replit workflow
felux.sql           – baseline DB schema
```

### Key root-level files

- `login.php`, `signup.php`, `signupform.php`, `loginform.php`, `loginpage1/2/3.php`
- `forget.php`, `resetpass.php`, `passform.php`, `check.php`, `checkEmailChange.php`
- `index.php`, `tutorial.php`, `static.php`, `premium.php`
- `divPage[0-15].php` and `divPagepayment.php`, `divPagereport.php`, `divPageticket.php`
- `buytool.php`, `buyscam.php`, `buytuto.php`
- `payment.php`, `addBalance.php`, `addBalanceAction.php`, `PMcheck.php`
- `openorder.php`, `orders.php`, `showorder.php`, `showProof.php`, `showReport.php`, `showTicket.php`
- `treport.php`, `tticket.php`, `tickets.php`, `reports.php`, `addReply.php`, `addReportReply.php`
- `becomeseller.php`, `setting.php`, `settingEdit.php`, `logout.php`
- `mailer.php`, `smtp.php`, `SMTPSend.php`, `check2mailer.php`, `check2smtp.php`, `check2cp.php`, `check2shell.php`, `apicheckcp.php`, `cPanel.php`, `PortChecker.php`, `rdp.php`, `banks.php`, `leads.php`, `scampage.php`, `cr.php`, `dd.php`, `test.php`, `router.php`, `encrypt.php`, `shell.php`, `ajax.php`, `ajaxinfo.php`

### Key admin files

- `admin/payments.php` (4-tab payments hub with search bar)
- `admin/withdrawAction.php` (pay/reject withdrawal AJAX endpoint)
- `admin/paymentapproval.php`, `admin/approvepayment.php`, `admin/rpay.php` (legacy)
- `admin/withdrawalreq.php` (legacy single-tab page)
- `admin/users.php`, `admin/usersb.php`, `admin/userss.php`, `admin/user.php`, `admin/userhistory.php`
- `admin/vendors/`, `admin/resseller.php`, `admin/ress.php`, `admin/viewr.php`
- `admin/reports.php`, `admin/refundr.php`, `admin/closereport.php`
- `admin/tickets.php`, `admin/openticket.php`, `admin/closeticket.php`, `admin/viewt.php`, `admin/assign.php`, `admin/assignedtome.php`
- `admin/news.php`, `admin/mnews.php`, `admin/sales.php`, `admin/dollar.php`
- `admin/header.php`, `admin/login.php`, `admin/lougout.php`, `admin/index.php`
- `admin/css/`, `admin/stylesheets/`, `admin/fonts/`, `admin/lib/`, `admin/ckeditor/`

### Key seller files

- `seller/login.php`, `seller/index.php`, `seller/header.php`
- `seller/sales.php`, `seller/withdrawal.php`, `seller/refund.php`
- `seller/ajax/updatebtc.php`, `seller/ajax/updateeth.php`
- `seller/rdpTab2.php`, `seller/rdpAdd.php`, plus per-category add/edit/delete forms
- Per-product management pages (cPanel, Shell, Mailer, SMTP, Leads, Premium, Banks, Scampage, Tutorial)

---

## Recent changes

- **Login**: fixed `Registrer` typo → `REGISTER`.
- **Admin Payments**:
  - Added a global **search bar** that filters every table across all four payment tabs.
  - **Pay** button on Withdraw Approval now actually approves: marks the request as done, records a successful payout in `rpayment`, and resets the seller's released balance.
  - New **Reject** button opens a modal where the admin must type a reason; the seller sees that reason on their withdrawal page.
  - New endpoint `admin/withdrawAction.php` handles pay/reject and auto-creates the `withdraw_note` column the first time it runs.
- **Seller withdrawal**: shows admin's rejection note and re-enables resubmission after a rejection.
- **Seller self-refund** added inside the report ticket (`seller/vr.php` → Refund modal → `seller/refund.php`). All refund paths now clamp `soldb`/`isold` at 0; the withdraw POST handler also refuses requests when the released balance is empty or below the $10 receive minimum.
- **Auto-created `refund` table**: `start.sh` now creates it on first launch so seller self-refunds work on a fresh DB.
- **Router**: `router.php` now resolves `name-<id>.html` URLs (e.g. `vr-1.html`, `vt-1.html`) to `name.php?id=<id>` — fixes the seller report 404.
- **Buyer report 404**: added `showReport(.*).html` rewrite in `buyer/.htaccess`.
- **Buyer report replies**: label corrected to `Buyer:<username>` in `addReportReply.php` and `buyer/addReportReply.php`.
- **Admin/Support refund**: `admin/refundr.php` posts `Refunded $X.XX successfully. Thank you for contacting us.` with a real `Admin:<user>` / `Support:<user>` label, sets the report to closed + accepted, and redirects back to `viewr.php`.
- **RDP listing**: added optional manual "Hosting / ISP" field on `seller/rdpTab2.php`; `seller/rdpAdd.php` prefers the manual value, falling back to ipwho.is, then "Unknown".
- **Proof uploads**: confirmed every `*Add.php` for cpanel/shell/mailer/smtp/lead/premium/banks/scampage/tutorial/rdp accepts an optional proof image via `seller/proofHelper.php::saveProof()`.
- **Seller `.htaccess`**: extensionless URL rewrite + per-report rules (line endings normalized to LF).
- Earlier: 10-hour profit hold ledger, unified payments hub, BTC/ETH withdraw method selector, refund-aware pipeline, cascading Country/State on RDP add form, restored buyer purchase confirmation modal.

---

## Latest fixes

### Seller ticket replies (`seller/vr.php`)
- Seller replies in report tickets are now shown anonymously as `Seller<id>` (looked up via `resseller.id`) instead of the seller's username. Admin's `viewr.php` still shows the real identity.

### Tutorials & scampages purchase flow
- `buyscam.php` and `buytuto.php` (root + `buyer/`) now correctly mark `sold='1'` after purchase.
- Listing queries on `buyer/divPage9.php` (tutorials) and `buyer/divPage10.php` (scampages) changed from `sold='0' or sold='1'` to `sold='0'` so sold items disappear from the storefront.
- Added no-cache headers to all `buyer/divPage1-10.php` so freshly bought items are not served from the proxy/browser cache.

### My Orders loop fix (PHP 8)
- `buyer/divPage15.php` line 92: `$row[id]` → `$row['id']` — bareword constants are a fatal error on PHP 8 and were halting the entire orders list.

### Sidebar badge counts
- `buyer/ajax.php` and `buyer/ajaxinfo.php` now filter `WHERE sold='0'` for tutorials and scampages so the sidebar badges reflect actual availability.

### My Listings page (`seller/mylistings.php`)
- Removed the duplicate DataTable init and renamed the table id from `dataTable` → `myListingsTable` to avoid the bootstrap4-datatables auto-init that was rendering "undefined" rows.

### Order reporting hardening (`buyer/treport.php`)
- Null-safe access for `$_GET['s']`, `$_GET['p']`, `$_GET['m']`, `$_GET['id']` (PHP 8 undefined-index warnings + base64_decode null deprecation).
- `(string)` cast inside `secu()` so a missing query parameter never reaches `base64_decode` as `null`.
- Date column fix: `reports.date` is a MySQL `DATE` column; we no longer write `d/m/Y H:i:s a` (which raised "Incorrect date value"). Now writes `Y-m-d H:i:s`.
- Duplicate-report check rewritten to use `COUNT(*)` with `>= 1`, and the INSERT moved inside the `else` branch so a duplicate request can no longer slip through.
- **Server-side 10-hour purchase window**: a buyer can no longer open a report more than 10 hours after the purchase — even by hitting the URL directly. The handler reads `purchases.date`, compares to `time()`, and rejects with an alert if expired.

### Refund / payout exploit prevention
This closes an "infinite money refund" path where a buyer (or a colluding seller) could open a report and trigger a refund **after** the seller had already been paid out for the order. Fixes are layered in three places:

- **`buyer/treport.php`** — before inserting a new report, we look up `seller_payments` by `purchase_id`. If the row's `status` is `released` (already paid) or `refunded`, the report is rejected with an alert and no DB write happens.
- **`seller/refund.php`** — the seller's "Refund" action now hard-blocks when the matching `seller_payments` row is `released` or `refunded`. The handler redirects back to the report view without touching `users.balance`, `reports.refunded`, `seller_payments`, or `resseller.soldb/isold`. This eliminates the duplicate-refund / post-payout-refund exploit.
- **`seller/vr.php`** — the **Refund** button itself is hidden when the payout is locked, replaced by an inline notice: *"This order has already been paid out and can no longer be refunded."* Combined with the server-side guard in `refund.php`, the button is both invisible and non-functional after release.

### Net effect
- Refunds are only possible while the seller payout is still in the 10-hour profit-hold window (`seller_payments.status='pending'`).
- Buyers cannot open report tickets more than 10 hours after their purchase, or at all once the payout has been released.
- Sellers cannot click their own Refund button after the payout is released, preventing balance-double-spend exploits.
