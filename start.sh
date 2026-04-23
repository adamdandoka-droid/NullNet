#!/usr/bin/env bash
set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
DATADIR="$ROOT/.data/mysql"
RUNDIR="$ROOT/.data/mysql-run"
SOCK="$RUNDIR/mysql.sock"
PIDFILE="$RUNDIR/mysql.pid"
MYCNF="$RUNDIR/my.cnf"
SETUPFLAG="$RUNDIR/setup_done"

mkdir -p "$RUNDIR" "$DATADIR"

cat > "$MYCNF" <<EOF
[mysqld]
datadir=$DATADIR
socket=$SOCK
pid-file=$PIDFILE
port=3306
bind-address=127.0.0.1
[client]
socket=$SOCK
EOF

wait_for_socket() {
  for i in $(seq 1 30); do
    if [ -S "$SOCK" ]; then
      return 0
    fi
    sleep 1
  done
  return 1
}

# If data directory is empty, initialize it
if [ ! -f "$DATADIR/ibdata1" ]; then
  echo "[start.sh] Initializing fresh MariaDB data directory..."
  mariadb-install-db --datadir="$DATADIR" --auth-root-authentication-method=normal --skip-test-db 2>&1 || true
  echo "[start.sh] Init complete."
fi

# Kill any leftover mariadbd process
if [ -f "$PIDFILE" ]; then
  OLD_PID=$(cat "$PIDFILE" 2>/dev/null || true)
  if [ -n "$OLD_PID" ] && kill -0 "$OLD_PID" 2>/dev/null; then
    echo "[start.sh] Stopping old MariaDB (pid=$OLD_PID)..."
    kill "$OLD_PID" 2>/dev/null || true
    sleep 2
  fi
  rm -f "$PIDFILE"
fi
rm -f "$SOCK"

# First-time setup: start with --skip-grant-tables to configure root
if [ ! -f "$SETUPFLAG" ]; then
  echo "[start.sh] First-time setup: starting MariaDB with --skip-grant-tables..."
  mariadbd --defaults-file="$MYCNF" --skip-grant-tables --skip-networking=0 >"$RUNDIR/mysql.log" 2>&1 &
  MYSQL_PID=$!
  echo "[start.sh] MariaDB pid=$MYSQL_PID (setup mode)"

  if ! wait_for_socket; then
    echo "[start.sh] MariaDB failed to start in setup mode. Log:"
    tail -20 "$RUNDIR/mysql.log"
    exit 1
  fi
  echo "[start.sh] MariaDB socket ready. Setting up root user..."

  mysql --socket="$SOCK" -u root <<'SQL'
FLUSH PRIVILEGES;
ALTER USER IF EXISTS 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL

  echo "[start.sh] Stopping setup-mode MariaDB..."
  kill "$MYSQL_PID" 2>/dev/null || true
  sleep 3
  rm -f "$SOCK" "$PIDFILE"
  touch "$SETUPFLAG"
  echo "[start.sh] First-time setup complete."
fi

# Start MariaDB normally
echo "[start.sh] Starting MariaDB..."
mariadbd --defaults-file="$MYCNF" --user="$(whoami)" >"$RUNDIR/mysql.log" 2>&1 &
MYSQL_PID=$!
echo "[start.sh] MariaDB pid=$MYSQL_PID"

for i in $(seq 1 30); do
  if mysql --socket="$SOCK" -u root -e "SELECT 1" >/dev/null 2>&1; then
    echo "[start.sh] MariaDB ready."
    break
  fi
  if [ $i -eq 30 ]; then
    echo "[start.sh] MariaDB failed to start. Log:"
    tail -40 "$RUNDIR/mysql.log"
    exit 1
  fi
  sleep 1
done

mysql --socket="$SOCK" -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS artmir;
CREATE USER IF NOT EXISTS 'artmir'@'localhost' IDENTIFIED BY 'Omeri1233';
GRANT ALL ON artmir.* TO 'artmir'@'localhost';
FLUSH PRIVILEGES;
SQL

if [ "$(mysql --socket="$SOCK" -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='artmir'")" = "0" ]; then
  echo "[start.sh] Importing felux.sql..."
  mysql --socket="$SOCK" -u root artmir < "$ROOT/felux.sql"
fi

echo "[start.sh] Applying schema migrations..."
mysql --socket="$SOCK" -u root artmir <<'SQL'
-- Create tables first before altering them
CREATE TABLE IF NOT EXISTS seller_payments (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id  INT NOT NULL,
  seller       VARCHAR(255) NOT NULL,
  buyer        VARCHAR(255) NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  status       VARCHAR(20) NOT NULL DEFAULT 'pending',
  item_type    VARCHAR(100) NOT NULL,
  s_id         INT NOT NULL,
  purchase_date DATETIME NOT NULL,
  release_date  DATETIME NOT NULL,
  approved_by  VARCHAR(100) DEFAULT NULL,
  approved_at  DATETIME DEFAULT NULL
);

ALTER TABLE reports
  ADD COLUMN IF NOT EXISTS subject  VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS type     VARCHAR(255) NOT NULL DEFAULT 'request',
  ADD COLUMN IF NOT EXISTS memo     TEXT NOT NULL,
  ADD COLUMN IF NOT EXISTS s_url    TEXT NOT NULL,
  ADD COLUMN IF NOT EXISTS admin_r  INT(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS refunded VARCHAR(255) NOT NULL DEFAULT 'Not Yet !',
  ADD COLUMN IF NOT EXISTS fmemo    TEXT NOT NULL,
  ADD COLUMN IF NOT EXISTS s_info   TEXT NOT NULL,
  ADD COLUMN IF NOT EXISTS state    VARCHAR(50) NOT NULL DEFAULT 'onHold';

ALTER TABLE payment
  ADD COLUMN IF NOT EXISTS tx_hash VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS note TEXT NOT NULL DEFAULT '';

-- Backfill missing payment ids and ensure auto_increment primary key
SET @needs_pk := (SELECT COUNT(*) FROM information_schema.columns
                  WHERE table_schema=DATABASE() AND table_name='payment'
                    AND column_name='id' AND extra LIKE '%auto_increment%');
SET @sql := IF(@needs_pk = 0,
  'UPDATE payment SET id = NULL WHERE id = 0',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @max := 0;
UPDATE payment SET id = (@max := @max + 1) WHERE id IS NULL ORDER BY date;
SET @sql := IF(@needs_pk = 0,
  'ALTER TABLE payment MODIFY id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

ALTER TABLE seller_payments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Ensure proof columns have a default so INSERTs without proof don't fail in strict mode
ALTER TABLE scampages  MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE stufs      MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE smtps      MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE banks      MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE tutorials  MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE mailers    MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE leads      MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE accounts   MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE rdps       MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE rdps       MODIFY IF EXISTS ram   VARCHAR(32)  NOT NULL DEFAULT '';
ALTER TABLE cpanels    MODIFY IF EXISTS proof VARCHAR(255) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS refund (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  ids       VARCHAR(64) NOT NULL DEFAULT '',
  type      VARCHAR(64) NOT NULL DEFAULT '',
  url       TEXT,
  price     VARCHAR(64) NOT NULL DEFAULT '',
  buyer     VARCHAR(255) NOT NULL DEFAULT '',
  sdate     VARCHAR(64) NOT NULL DEFAULT '',
  rdate     VARCHAR(64) NOT NULL DEFAULT '',
  resseller VARCHAR(255) NOT NULL DEFAULT '',
  INDEX (buyer),
  INDEX (resseller)
);

CREATE TABLE IF NOT EXISTS rpayment (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  username  VARCHAR(255) NOT NULL,
  amount    VARCHAR(64) DEFAULT '',
  abtc      VARCHAR(64) DEFAULT '',
  adbtc     VARCHAR(255) DEFAULT '',
  method    VARCHAR(64) DEFAULT 'cashout',
  date      VARCHAR(64) DEFAULT '',
  url       TEXT,
  urid      VARCHAR(64) DEFAULT '0',
  rate      VARCHAR(64) DEFAULT '',
  fee       VARCHAR(64) DEFAULT '',
  INDEX (username)
);
SQL

cleanup() {
  echo "[start.sh] Shutting down..."
  if [ -n "$MYSQL_PID" ] && kill -0 "$MYSQL_PID" 2>/dev/null; then
    kill "$MYSQL_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT INT TERM

echo "[start.sh] Starting PHP server on 0.0.0.0:5000..."
exec php -S 0.0.0.0:5000 -t "$ROOT" "$ROOT/router.php"
