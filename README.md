# handleexception (Invoices App)

Laravel invoicing app deployed on a single AWS EC2 instance via Terraform + Docker Compose.

- **Live URL:** https://www.handleexception.com (canonical — apex and HTTP both 301 redirect here)
- **Server:** AWS EC2, `eu-west-2` (London), Elastic IP `18.130.3.250`
- **Stack:** nginx (Docker) → PHP-FPM app container → MySQL container, TLS via Let's Encrypt
- **IaC:** Terraform (`terraform/`), state in S3 (`handleexception-terraform-state`)

---

## Connecting to the server

The EC2 security group only allows SSH from a **single whitelisted IP** (`ssh_allowed_cidr` in `terraform/terraform.tfvars`). If your IP has changed since the last apply, SSH (and EC2 Instance Connect / Session Manager) will simply time out or refuse — this is the most common connection failure.

**1. Check/update the allowed IP**

```bash
curl -s ifconfig.me   # your current public IP
```

Compare against `ssh_allowed_cidr` in `terraform/terraform.tfvars`. If it doesn't match, update it and apply **only the security group** (never a plain `terraform apply` — see warning below):

```bash
cd terraform
terraform apply -target=module.ec2.aws_security_group.this
```

**2. SSH in**

The deploy key is `~/.ssh/id_ed25519_personal` (public key matches `ssh_public_key` in `terraform.tfvars`, tied to `kyleross.pretorius@gmail.com`).

```bash
ssh -i ~/.ssh/id_ed25519_personal ubuntu@handleexception.com
# or
ssh -i ~/.ssh/id_ed25519_personal ubuntu@18.130.3.250
```

If you don't have that key or it's a new machine, note: `~/.ssh/id_ed25519` and `id_personal` are **decoys** for this project — they don't match the key pair Terraform deployed (`handleexception-deployer`). Only `id_ed25519_personal` works.

**3. Fallback: AWS Console browser SSH**

EC2 console → Instances → select instance → **Connect** → EC2 Instance Connect or Session Manager tab. Note: Instance Connect's browser SSH comes from an AWS-owned IP range, not your own — so it's *also* blocked by the same security group rule until your IP (or a broader CIDR) is whitelisted. Session Manager works over SSM instead of port 22 and bypasses the security group entirely, but requires the instance to have an IAM role with `AmazonSSMManagedInstanceCore` attached (not currently configured on this instance).

---

## Terraform — important gotchas

⚠️ **Do not run a plain `terraform apply` without checking the plan first.** The EC2 module's AMI lookup (`data.aws_ami.ubuntu`, `modules/ec2/main.tf`) always resolves to the *most recent* Ubuntu 24.04 image. Since a newer AMI is published periodically, an untargeted `apply` will show the instance as needing **replacement** (destroy + recreate) — wiping the running server, its Docker state, and the Let's Encrypt cert — just because a newer base image now exists. Always run `terraform plan` first and read it.

Safe pattern for routine changes (e.g. updating the allowed SSH IP):

```bash
terraform plan                                              # inspect first — look for "must be replaced"
terraform apply -target=module.ec2.aws_security_group.this  # scope to just what you're changing
```

**TODO / recommended fix:** pin the AMI to a specific ID (or use `lifecycle { ignore_changes = [ami] }` on `aws_instance.this`) so routine applies stop threatening the instance.

### Common commands

```bash
cd terraform
terraform init                # first-time / after adding providers
terraform plan                # always run before apply
terraform output              # list all outputs
terraform output -raw elastic_ip
terraform output -raw iam_secret_access_key   # sensitive, S3 IAM user
```

State is remote (S3, profile `handleexception`) — no local `terraform.tfstate` to worry about, but you do need the `handleexception` AWS CLI profile configured locally (`~/.aws/credentials`) to run anything.

---

## Deploying the app

`scripts/deploy.sh` handles both first-time setup and routine deploys. It resolves the target host from `terraform output -raw elastic_ip`, so Terraform must be applied first.

```bash
./scripts/deploy.sh --setup   # first time only: clone repo, copy .env, obtain TLS cert, install renewal cron
./scripts/deploy.sh           # routine deploy: pull code, rebuild containers, migrate, cache
```

Routine deploy does, in order: copy `.env` → `git pull` → `docker compose up -d --build` → wait for MySQL → `php artisan migrate --force` → cache config/routes/views.

### Deploying a code change — step by step

1. **Commit and push locally.** The server pulls from GitHub (`origin` → `github-personal:kylerosspretorius/handleexception.git`, branch `main`), so your changes need to be pushed there first — `deploy.sh` never pushes for you.

   ```bash
   git add -A
   git commit -m "your message"
   git push origin main
   ```

2. **Run the deploy script** from your local machine (needs the `handleexception` AWS profile configured, since it resolves the host via `terraform output`):

   ```bash
   ./scripts/deploy.sh
   ```

   This SSHes in, pulls `main`, rebuilds any changed Docker images, restarts containers, runs pending migrations, and re-caches config/routes/views. Watch the output — migration or build failures print inline.

3. **Verify:**

   ```bash
   curl -sI https://www.handleexception.com/   # expect 200
   ```

   Or just load the site.

### Things that need an extra step beyond `deploy.sh`

- **Changed `.env` values** (API keys, `APP_URL`, mail/DB config): `deploy.sh` copies your *local* `.env` over the server's on every run, so edit it locally first, then deploy. Watch out — this means the server's `.env` always mirrors your local one; don't let secrets diverge by hand-editing on the server without pulling the change back down locally too.
- **Changed nginx config** (`docker/nginx/prod.conf`): not copied by `deploy.sh` automatically. Copy it manually and reload:

  ```bash
  scp -i ~/.ssh/id_ed25519_personal docker/nginx/prod.conf ubuntu@handleexception.com:/var/www/handleexception/docker/nginx/prod.conf
  ssh -i ~/.ssh/id_ed25519_personal ubuntu@handleexception.com \
    'cd /var/www/handleexception && docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T nginx nginx -t && docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T nginx nginx -s reload'
  ```

  Always run `nginx -t` (config test) before `-s reload` — a bad config leaves nginx running on the old config rather than crashing, so it's easy to miss a typo otherwise.

- **New/changed dependencies** (`composer.json`, `package.json`): covered by `deploy.sh`'s `--build` flag as long as they're rebuilt inside the `Dockerfile` build step — check the container actually rebuilt (`docker compose images` timestamps, or just watch the build log during deploy) rather than assuming a plain `up -d` picked up the change.
- **New migrations:** covered automatically by `deploy.sh` (`php artisan migrate --force`). For anything destructive (column drops, data migrations), consider running it manually first with `--pretend` to review, and always take a DB snapshot first for anything irreversible.

### Rolling back

`deploy.sh` doesn't version releases — it just deploys whatever `main` currently is. To roll back:

```bash
git revert <bad-commit>   # or: git reset --hard <last-good-commit> (rewrites history — coordinate before pushing)
git push origin main
./scripts/deploy.sh
```

---

## Docker Compose layout

Two compose files are merged in production: `docker-compose.yml` (base) + `docker-compose.prod.yml` (overrides).

```bash
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

$COMPOSE ps                          # container status
$COMPOSE logs -f nginx               # tail nginx logs
$COMPOSE logs -f app                 # tail Laravel/PHP-FPM logs
$COMPOSE exec app php artisan tinker # shell into Laravel
$COMPOSE exec app bash               # shell into app container
$COMPOSE restart nginx               # reload nginx (e.g. after a cert renewal, config change)
```

Key differences in prod override:
- nginx binds host ports **80/443 directly** (base file uses `8080:80` for local dev)
- nginx mounts `/etc/letsencrypt` read-only for the TLS cert
- MySQL has no host port exposed in prod (base exposes `3307:3306` for local access)

Nginx config: `docker/nginx/prod.conf` (production) vs `docker/nginx/default.conf` (local).

---

## TLS certificate (Let's Encrypt / certbot)

**How it's issued:** `certbot certonly --standalone` on the **host** (not in a container) — this needs port 80 free, which conflicts with the always-on nginx container. See incident below.

**Renewal automation (as of 2026-09-24):**
- systemd `certbot.timer` (from `apt install certbot`) — runs twice daily, checks all certs, only actually renews within 30 days of expiry
- Renewal hooks at `/etc/letsencrypt/renewal-hooks/pre/stop-nginx.sh` and `/etc/letsencrypt/renewal-hooks/post/start-nginx.sh` — stop/start the nginx container around any renewal attempt so `standalone` can bind port 80. **These are the fix for the Aug 2026 outage below and must not be removed.**
- A legacy cron job at `/etc/cron.d/certbot-renew.disabled` (installed by `deploy.sh --setup`, now disabled) duplicated the systemd timer without ever freeing port 80 — safe to leave disabled or delete.

**Manually check / renew:**

```bash
sudo certbot certificates                 # check status + expiry of all certs
sudo systemctl status certbot.timer       # confirm the timer is active
sudo certbot renew --dry-run              # test renewal without changing anything
```

**Force a full reissue** (e.g. cert already expired, or hooks weren't in place yet) — covers both apex and `www`:

```bash
COMPOSE="docker compose -f /var/www/handleexception/docker-compose.yml -f /var/www/handleexception/docker-compose.prod.yml"
$COMPOSE stop nginx
sudo certbot certonly --standalone -d handleexception.com -d www.handleexception.com \
  --cert-name handleexception.com --non-interactive --agree-tos \
  -m kyleross.pretorius@gmail.com --force-renewal
$COMPOSE start nginx
```

⚠️ Always include **both** `-d handleexception.com -d www.handleexception.com`. The cert issued during the Sep 2026 incident fix initially only covered the apex domain, which left `https://www.handleexception.com` failing with a hostname-mismatch error (`NET::ERR_CERT_COMMON_NAME_INVALID` / browser "Not secure") even though the apex domain was fine — nginx's `www` server block (`prod.conf`) presents the same cert for that hostname, so it needs to be in the SAN list too. Check current coverage with:

```bash
echo | openssl s_client -connect handleexception.com:443 -servername handleexception.com 2>/dev/null \
  | openssl x509 -noout -text | grep -A2 "Subject Alternative Name"
```

Renewal logs: `/var/log/letsencrypt/letsencrypt.log`

### Incident history

**2026-09-24 — cert expired Aug 14, 2026, ~6 weeks undetected.** Root cause: `certbot renew` used the `standalone` authenticator, which needs to bind port 80 itself — but the Docker nginx container holds port 80 permanently, so every renewal attempt failed with `Could not bind TCP port 80 because it is already in use`. This had been silently failing since the cert was first issued; nothing ever freed the port for renewal. Fixed by adding the pre/post renewal hooks described above (stop nginx → renew → start nginx) and manually reissuing the cert (new expiry: **2026-12-23**). Also found the SSH security group CIDR was stale (pointed at an old home/office IP), which blocked both direct SSH and EC2 Instance Connect during troubleshooting — updated via targeted `terraform apply`.

**Recommendation:** if this repeats, check whether Let's Encrypt is emailing expiry warnings to `kyleross.pretorius@gmail.com` (the registered contact) — those give a 20-day heads-up and were presumably missed or filtered.

---

## Project structure

```
handleexception/
├── app/                    Laravel application code
├── bootstrap/, config/     Laravel framework bootstrap + config
├── database/               Migrations, seeders, factories
├── docker/
│   └── nginx/
│       ├── default.conf    Local dev nginx config (port 8080)
│       └── prod.conf       Production nginx config (port 80/443, TLS, redirects www→apex)
├── docker-compose.yml       Base compose (local dev defaults)
├── docker-compose.prod.yml  Production overrides (ports, TLS mount, env)
├── Dockerfile               PHP-FPM app image
├── public/                  Laravel public webroot
├── resources/                Views, frontend assets
├── routes/                   Laravel routes
├── scripts/
│   └── deploy.sh            Deploy script (setup + routine deploy)
├── storage/                  Laravel storage (logs, cache, uploads)
├── terraform/
│   ├── main.tf              Root module — wires ec2/s3/dns modules, S3 backend config
│   ├── variables.tf          Root input variables
│   ├── outputs.tf             app_url, elastic_ip, ssh_command, s3_bucket_name, IAM keys
│   ├── terraform.tfvars       Actual values (gitignored except .example) — SSH CIDR, keys, region
│   └── modules/
│       ├── ec2/               Instance, security group, key pair, Elastic IP, user_data (Docker/certbot install)
│       ├── s3/                 S3 bucket for invoice PDFs/logos + IAM user/policy for app access
│       └── dns/                 Route 53 A records (apex + www)
├── tests/                    PHPUnit tests
└── .env                       Local secrets (DB, AWS keys, mail, etc.) — not committed
```

## Key infrastructure facts

| Item | Value |
|---|---|
| AWS region | `eu-west-2` (London) |
| EC2 instance type | `t2.micro` (free tier) |
| Elastic IP | `18.130.3.250` |
| Security group | `handleexception-invoices` (SSH restricted, HTTP/HTTPS open) |
| S3 bucket | `handleexception-customer-data` (invoice PDFs, logos) |
| Terraform state | S3 `handleexception-terraform-state`, key `invoices/terraform.tfstate`, AWS profile `handleexception` |
| Route 53 zone | `handleexception.com` (must pre-exist; not created by Terraform) |
| SSH key pair (AWS) | `handleexception-deployer` |
| SSH private key (local) | `~/.ssh/id_ed25519_personal` |
| DB (prod) | MySQL 8.0, container `handleexception_mysql`, no host port exposed |
| Web server | nginx 1.25-alpine (Docker), reverse-proxies PHP to `app:9000` |
| PHP | 8.4 (see `Dockerfile`) |

## Quick troubleshooting checklist

- **Can't SSH / Instance Connect fails:** check your current IP (`curl ifconfig.me`) against `ssh_allowed_cidr` in `terraform.tfvars`; update + targeted apply if stale.
- **Site down / 502:** `docker compose ps` — check `app` and `nginx` containers are `Up`; `docker compose logs app` for PHP errors.
- **Cert expired / browser warning:** `sudo certbot certificates`; if expired, run the manual reissue steps above; verify the renewal hooks in `/etc/letsencrypt/renewal-hooks/{pre,post}/` still exist.
- **`terraform plan` shows instance replacement:** almost certainly the AMI drift issue above — do **not** apply untargeted; use `-target` for the specific resource you meant to change.
- **DB connection errors after deploy:** MySQL container may not be ready yet — `deploy.sh` waits up to 90s, but check `docker compose logs mysql` if migrations failed.
