#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# deploy.sh — Deploy handleexception invoices app to EC2
#
# Usage:
#   First-time:  ./scripts/deploy.sh --setup
#   Subsequent:  ./scripts/deploy.sh
# ---------------------------------------------------------------------------

REMOTE_USER="ubuntu"
APP_DIR="/var/www/handleexception"
COMPOSE="docker compose -f $APP_DIR/docker-compose.yml -f $APP_DIR/docker-compose.prod.yml"
EMAIL="kyleross.pretorius@gmail.com"
DOMAIN="handleexception.com"

# Resolve EC2 IP from Terraform output
REMOTE_HOST=$(cd "$(dirname "$0")/../terraform" && terraform output -raw elastic_ip 2>/dev/null)

if [ -z "$REMOTE_HOST" ]; then
  echo "ERROR: Could not resolve elastic_ip from Terraform output. Run terraform apply first."
  exit 1
fi

SSH="ssh -A -i ~/.ssh/id_ed25519_personal -o StrictHostKeyChecking=no ubuntu@$REMOTE_HOST"

echo "==> Deploying to $REMOTE_HOST"

# ---------------------------------------------------------------------------
# First-time setup
# ---------------------------------------------------------------------------
if [ "${1}" = "--setup" ]; then
  echo "==> [setup] Cloning repo"
  $SSH "ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null; git clone git@github.com:kylerosspretorius/handleexception.git $APP_DIR || (cd $APP_DIR && git pull)"

  echo "==> [setup] Copying .env"
  scp .env "$REMOTE_USER@$REMOTE_HOST:$APP_DIR/.env"

  echo "==> [setup] Obtaining TLS certificate (port 80 must be free)"
  $SSH "cd $APP_DIR && $COMPOSE down 2>/dev/null; sudo systemctl stop nginx 2>/dev/null; sudo systemctl disable nginx 2>/dev/null; sudo certbot certonly --standalone -d $DOMAIN --non-interactive --agree-tos -m $EMAIL"

  echo "==> [setup] Installing certbot renewal hook"
  $SSH "echo '0 3 * * * root certbot renew --quiet --deploy-hook \"$COMPOSE exec -T nginx nginx -s reload\"' | sudo tee /etc/cron.d/certbot-renew > /dev/null"
fi

# ---------------------------------------------------------------------------
# Deploy (runs on every call, including after --setup)
# ---------------------------------------------------------------------------
echo "==> Pulling latest code"
$SSH "ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null; cd $APP_DIR && git pull"

echo "==> Building and starting all containers"
$SSH "cd $APP_DIR && $COMPOSE up -d --build"

echo "==> Waiting for MySQL to be ready"
$SSH "cd $APP_DIR && until $COMPOSE exec -T mysql mysqladmin ping -u root -p\${DB_ROOT_PASSWORD:-rootpassword} --silent 2>/dev/null; do echo 'waiting for mysql...'; sleep 3; done"

echo "==> Running migrations"
$SSH "cd $APP_DIR && $COMPOSE exec -T app php artisan migrate --force"

echo "==> Clearing and warming caches"
$SSH "cd $APP_DIR && $COMPOSE exec -T app php artisan config:cache"
$SSH "cd $APP_DIR && $COMPOSE exec -T app php artisan route:cache"
$SSH "cd $APP_DIR && $COMPOSE exec -T app php artisan view:cache"

echo ""
echo "==> Done. App is live at https://$DOMAIN"
