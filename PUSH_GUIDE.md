# GitHub Push Guide — NullNet Script V2.0

## Quick Push (normal day-to-day)

After making changes in Replit, open the **Shell** tab and run:

```bash
git add -A
git commit -m "your message here"
git push origin master
```

---

## First-Time Setup (or if the remote changes)

Set your GitHub remote with your personal access token:

```bash
git remote set-url origin https://YOUR_TOKEN@github.com/adamdandoka-droid/NullNet.git
```

Then push:

```bash
git push origin master
```

> Get a token at: https://github.com/settings/tokens
> Required scope: **repo** (full control of private repositories)

---

## If Push Is Rejected (non-fast-forward)

This happens when the remote has commits you don't have locally:

```bash
git push origin master --force
```

> Only use `--force` when you are sure you want to overwrite the remote.

---

## Nuclear Fix (if push keeps failing with pack errors)

This creates a clean git history and force pushes all current files:

```bash
cd /tmp
mkdir nullnet-push
cp -r ~/workspace/. nullnet-push/
cd nullnet-push
rm -rf .git
git init
git add -A
git commit -m "NullNet clean push"
git push https://YOUR_TOKEN@github.com/adamdandoka-droid/NullNet.git master --force
```

This bypasses any corrupted git history and pushes a clean snapshot.

---

## Token Security Tips

- **Never share your token in chat or commit it to code**
- Tokens can be revoked at: https://github.com/settings/tokens
- Generate a new token after every push from a shared environment
- Set an expiration date on tokens (30 or 90 days recommended)

---

## What NOT to commit

Add these to `.gitignore` to avoid bloating your repo:

```
.data/
.local/
vendor/
composer.phar
*.sql
*.zip
*.log
```
