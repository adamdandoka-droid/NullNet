# NullNet — Marketplace Platform

NullNet is a multi-role PHP marketplace where users can buy and sell digital goods (RDPs, cPanels, Shells, Mailers, SMTPs, Leads, Premium Accounts, Banks, Scampages, Tutorials). It includes a buyer storefront, a seller back-office, an admin/staff control panel and a built-in support/ticketing flow.

The codebase is plain PHP (procedural with `mysqli`), Bootstrap 3, jQuery and a few helper libraries (Bootbox, SweetAlert, DataTables). It is served by Apache via `start.sh` and uses MariaDB/MySQL.

---

## 1. Tech stack & runtime

- **Language**: PHP 7+ (procedural, mysqli, no frameworks)
- **Database**: MySQL/MariaDB (`includes/config.php` builds `$dbcon`)
- **Web server**: Apache (started by `start.sh`, port-bound by Replit workflow)
- **Frontend**: Bootstrap 3 + jQuery + Bootbox + SweetAlert + DataTables
- **External JSON**: `cdn.jsdelivr.net/gh/dr5hn/countries-states-cities-database` (cascading country/state dropdowns on RDP form)
- **Workflow**: `Start application` runs `bash ./start.sh`
- **Line endings**: most files are CRLF + tabs. Prefer `php -r` / `str_replace` over multi-line `edit` when in doubt.

---

## 2. Top-level directory layout

```
/                   – buyer-facing storefront (index, login, divPage1..10.php, buy*.php, etc.)
admin/              – admin/staff control panel (payments hub, users, products, reports …)
seller/             – seller back-office (RDP/Shell/Mailer/etc add forms, sales, withdrawal)
support/            – staff support agents (tickets, refunds with limited scope)
buyer/              – legacy buyer subsite (mirrors of root pages used in some flows)
includes/           – shared `config.php` (DB), `header.php`, common helpers
files/              – uploaded assets (proof images, ID docs, etc.)
_lib/, vendor/      – third-party JS/CSS libs
```

---

## 3. Roles

| Role     | Login lives in              | Session keys                |
|----------|-----------------------------|-----------------------------|
| Buyer    | `/login.php`                | `$_SESSION['uname']`        |
| Seller   | `/seller/login.php`         | `$_SESSION['sname']`        |
| Admin    | `/admin/login.php`          | `$_SESSION['aname']`        |
| Support  | `/support/login.php`        | `$_SESSION['supname']`      |

`users.resseller = '1'` flags a user as a seller (also gives them access to `/seller/`). Admin/Support users live in their own tables (`admin`, `support`).

---

## 4. Important database tables

- `users` — buyer/seller accounts (`balance`, `resseller`, `email`, …)
- `resseller` — per-seller record (`soldb` = released/withdrawable, `unsoldb`, `isold`, `iunsold`, `withdrawal`, `withdraw_method`, `btc`, `eth`, `allsales`, …)
- `purchases` — every buyer purchase (`id`, `username`, `resseller`, `acctype`, `s_id`, `s_url`, `price`, `date`, `reported`)
- `seller_payments` — **profit-hold ledger** (`id`, `seller`, `purchase_id`, `amount`, `status`, `release_date`, `released_at`, `approved_at`)
  - `status` ∈ `pending` | `released` | `approved` | `refunded`
- `payment` — buyer balance top-up requests (BTC / ETH)
- `rpayment` — seller payouts that have been paid out
- `reports` — buyer↔seller dispute tickets (refundable from `admin/refundr.php`, `support/refundr.php`, `seller/refund.php`)
- `refund` — refund history
- Per-product tables: `rdp`, `cpanel`, `shell`, `mailer`, `smtp`, `leads`, `premiumaccount`, `banks`, `scampage`, `tutorial`

---

## 5. The seller profit-hold pipeline (10-hour hold)

This is the core money flow added recently. The rule is simple: **a sale only becomes withdrawable after a 10-hour buyer-protection window with no report.**

### 5.1 At purchase time
`buytool.php`, `buyscam.php`, `buytuto.php` (and the per-category `buy*.php`) all do:
1. Insert into `purchases`.
2. Insert into `seller_payments` with `status='pending'` and `release_date = NOW()+10h`.
3. **Do NOT** add to `resseller.soldb`. Increment `isold` only.
4. Increment `users.balance -= price` for the buyer.
5. Return JSON `{success:true, orderid, price, item}` to the front end.

### 5.2 Auto-release sweep
Runs whenever an authenticated seller opens **`/seller/sales.php`** or **`/seller/withdrawal.php`**, or when an admin opens **`/admin/payments.php`**:

```
SELECT pending rows in seller_payments WHERE release_date <= NOW()
For each row:
   if matching purchases.reported is empty → status='released', soldb += amount
   else                                    → leave frozen
```

Reported sales never auto-release; they wait for an admin/support resolution.

### 5.3 Early approval
Admin can hit **Approve Now** on the *Seller Profit Hold* tab in `/admin/payments.php` (handled by `admin/approvepayment.php`). It flips the row to `approved` and credits `soldb` immediately.

### 5.4 Refund-aware
`admin/refundr.php`, `support/refundr.php`, `seller/refund.php` look up the matching `seller_payments` row first:
- If still `pending` → mark it `refunded`, no `soldb` change.
- If already released/approved → subtract from `soldb` as before.

---

## 6. Seller withdrawal flow

`/seller/withdrawal.php` (rebuilt) has three tabs:

- **Sales** – Total Sales, Pending Hold (count + $), Released (= `soldb`), Share 65%, You Receive. If receive ≥ $10 and no pending request, the seller picks **BTC / ETH** and submits. Submission sets `withdrawal='requested'` and `withdraw_method='btc'|'eth'`.
- **Edit Address** – Two independent inputs (BTC + ETH) with their own save buttons (`seller/ajax/updatebtc.php`, `seller/ajax/updateeth.php`).
- **Payment History** – Past payouts from `rpayment`.

The button is disabled until the chosen method's address is set.

Admin processes the request from **`/admin/payments.php` → Withdraw Approval** tab, which displays method + correct address (BTC or ETH), then opens `PaySeller.html?...&method=...` for the actual payout.

---

## 7. Admin payments hub

`/admin/payments.php` consolidates four tabs (sidebar entry "Payments" with combined badge in `admin/header.php`):

1. **Withdraw Approval** – Pending seller payout requests (released $, pending hold, reported, receive USD/BTC, method, address, NullNet 35%, Pay button).
2. **Payment Approval** – Buyer balance top-up requests (BTC/ETH); approve credits `users.balance`, reject denies.
3. **Seller Profit Hold** – Every pending `seller_payments` row with live JS countdown and **Approve Now** to release early.
4. **All Payments History** – Combined chronological feed of balance top-ups + purchases + seller-profit credits.

Legacy pages still on disk for backend actions: `admin/withdrawalreq.php`, `admin/paymentapproval.php`, `admin/approvepayment.php`. The sidebar links to them have been replaced with the unified Payments entry.

---

## 8. Buyer purchase confirmation GUI

Each storefront detail page (`divPage1.php` … `divPage10.php` at the project root, plus their `buyer/` mirrors) defines a `buythistool(id)` function. It now:

1. Asks for confirmation via `bootbox.confirm`.
2. POSTs to the matching `buy*.php` (`buytool.php`, `buyscam.php`, `buytuto.php`).
3. Parses the JSON response and shows a success modal with order #, item name, price, and a **My Orders** jump button. On `sold` / `nobalance` / network errors, shows an appropriate alert.
4. Animates the row out of the listing on success.

---

## 9. Seller RDP listing form (cascading geography)

`seller/rdpTab2.php` replaced the free-text Country/State inputs with two `<select>`s. On load it fetches the bundled JSON from jsdelivr (cached). Selecting a country populates that country's states. The submitted values still flow into `seller/rdpAdd.php` unchanged. Hosting type and proof image upload are also handled here.

---

## 10. Conventions & gotchas

- **Editing files**: most files are CRLF + tabs. Prefer `php -r` / `str_replace` over `edit` for tricky multi-line replacements.
- **Sessions**: every authenticated page calls `session_start()` early; admin pages also include `admin/header.php` which gates by `$_SESSION['aname']`.
- **No virtualenv / Docker / Python**: use PHP CLI for scripted file transforms and `mysql` CLI for DB checks (creds in `includes/config.php`).
- **Routing**: many admin URLs end in `.html` thanks to rewrites in `admin/.htaccess` (e.g. `payments.html` → `payments.php`).
- **No `eth` column existed before**: ETH support added an `eth` and `withdraw_method` column on `resseller`. Migration was run as a one-off `ALTER TABLE`.
- **Avoid silent fallbacks**: `buy*.php` returns explicit JSON statuses (`success`, `sold`, `nobalance`, `error`); the front end branches on them.

---

## 11. Where to look first when making changes

| You want to change…                          | Start in                                               |
|---------------------------------------------|--------------------------------------------------------|
| The 10-hour hold rule                       | `buytool.php`, `seller/sales.php`, `admin/payments.php`|
| Seller withdraw UX                          | `seller/withdrawal.php`, `seller/ajax/update{btc,eth}.php` |
| Admin sidebar / nav                         | `admin/header.php`                                     |
| Buyer purchase confirmation modal           | root `divPage[1-10].php` + matching `buy*.php`         |
| RDP form / cascading dropdowns              | `seller/rdpTab2.php`, `seller/rdpAdd.php`              |
| Refund logic                                | `admin/refundr.php`, `support/refundr.php`, `seller/refund.php` |
| DB connection                               | `includes/config.php`                                  |

---

## 12. Recent changes log (high level)

- Cascading Country/State dropdowns on the RDP add form.
- Real 10-hour profit hold via `seller_payments`; auto-release sweeps in seller and admin pages.
- Rebuilt `seller/sales.php` with status badges, live countdown and stats.
- New consolidated `admin/payments.php` (4 tabs) replacing the separate withdraw/payment approval pages.
- Buyer purchase modal restored across all categories (root `divPage[1-10].php`).
- Refund flows are now profit-hold aware (cancel pending vs. deduct released).
- Seller withdrawal page rewritten: correct numbers (Total Sales / Pending / Released / Receive), working **Withdraw** button, BTC/ETH method selector, dual address editor.

### April 2026 fixes

**Seller "Add" forms (`scampageAdd.php`, `leadAdd.php`, `tutorialAdd.php`, `premiumAdd.php`, `banksAdd.php`)**
- Replaced the broken `mysqli_fetch_array(...,MYSQLI_NUM); if($data[0] > 1)` duplicate-detection pattern (which threw PHP 8 null-offset warnings and falsely flagged low-id rows) with `mysqli_num_rows($rs) > 0`.
- Replaced silent `or die();` and `or die("error here")` with `or die(mysqli_error($dbcon))` so DB-level INSERT failures surface to the AJAX caller.
- Stripped a stray leading space before `<?php` that was emitted to the browser before any code ran. The space silently broke `header("Location: ...")` and made unauthenticated POSTs return a 1-byte body — which the AJAX `$.trim()` then turned into the misleading **"No response from server"** message. Same whitespace bug fixed in all `seller/*Tab1.php` files (cpanelTab1, leadTab1, mailerTab1, scampageTab1, shellTab1, tutorialTab1) where it was producing `session_start(): Cannot start session after headers already sent` warnings.
- `shellAdd.php` PHP 8.1 deprecation: handle `parse_url()` returning null by retrying with `http://` prefix and casting to string.

**Seller "All" tab (every tool: cpanel/banks/lead/mailer/premium/rdp/scampage/shell/smtp/tutorial)**
- Fixed the `$qu` query so listings actually appear: previously the leading-whitespace bug made the page redirect-fail before reaching the query. Now the query runs cleanly and shows the seller's own active items (`resseller='$uid' AND sold='0'`).

**Seller "My Orders" / openorder modal**
- Added a `showorder` → `openorder.php` alias in `router.php` because the built-in PHP dev server ignores `.htaccess` `RewriteRule`s. Both seller and buyer "View Order" modals now load order details. Same mechanism (the `$aliases` map in `router.php`) is the place to mirror any other `.htaccess`-only rewrites.

**Admin dashboard (`admin/index.php`)**
- **NullNet Sales $ meter** — was stuck at zero because the four time buckets only covered the 4 prior days and never included today. Replaced the four ad-hoc queries with a single `_sumSalesForDay()` helper and re-bucketed the chart to **-3d, -2d, Yesterday, Today**. Sales now appear the same day they're made.
- **Registered users meter** — was stuck at zero because the four `$today/$yesterday/...` variables used `m-d-y` format while `users.datereg` is stored as `Y-m-d` (`DATE`). Switched the variables to `Y-m-d` so the equality match works.
- **New stat card: Seller Payouts** — added a 5th card showing the running total of approved/released seller payouts (`SUM(amount) FROM seller_payments WHERE status IN ('approved','released','paid','completed')`) and the number of payouts. Re-laid out the stat row from `col-lg-3 ×4` to `col-md-6 col-lg-2 ×5`.

**Storefront pie chart (`static.php` + `buyer/static.php`) — clickable categories**
- Added a Google Charts `select` listener on the donut. Clicking any slice (Leads, cPanels, Shells, Rdps, Mailers, Smtps, Scampages, Tutorials, Premium/Dating/Shop, Banks) navigates the parent page to that category via `parent.pageDiv(n, title, url, 0)`, falling back to `window.top.location.href` when not embedded in an iframe. Donut now shows a pointer cursor.
