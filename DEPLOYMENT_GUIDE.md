# Deployment guide — EC2 + RDS (MySQL) + DynamoDB

This walks through standing up the whole thing from scratch. Follow it in
order — DynamoDB and RDS both need to exist before the app on EC2 can talk
to them.

## 1. Launch the EC2 instance

1. EC2 console → **Launch instance**.
2. AMI: Amazon Linux 2023 (or Ubuntu 22.04 — commands below are for Amazon
   Linux; adjust package names if you pick Ubuntu).
3. Instance type: `t2.micro` / `t3.micro` is enough for this app.
4. **Create or attach an IAM role** on the instance with a policy allowing
   `dynamodb:GetItem`, `dynamodb:Query`, `dynamodb:Scan`, `dynamodb:PutItem`,
   `dynamodb:CreateTable`, `dynamodb:DescribeTable` on the table you'll
   create in step 3. This is what lets `skills.php` reach DynamoDB with no
   access keys on disk.
5. Security group: allow inbound **HTTP (80)** and **SSH (22)** from your IP.
6. Launch, then SSH in:
   ```
   ssh -i your-key.pem ec2-user@<EC2_PUBLIC_IP>
   ```

## 2. Install PHP, Apache, and Composer

```bash
sudo dnf update -y
sudo dnf install -y httpd php php-pdo php-mysqlnd unzip
sudo systemctl enable --now httpd

# Composer (for the AWS SDK)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 3. Create the RDS MySQL instance

1. RDS console → **Create database** → MySQL → Free tier / dev template.
2. DB instance identifier: `student-portfolio-db`.
3. Set a master username/password (used only to run `schema.sql` once).
4. **Public access**: No — instead, in the RDS security group, allow
   inbound MySQL/Aurora (3306) **from the EC2 instance's security group**,
   not from the internet.
5. Once available, note the endpoint hostname.
6. From the EC2 instance (or anywhere with network access to the RDS
   security group), load the schema:
   ```bash
   mysql -h <RDS_ENDPOINT> -u <master_user> -p < sql/schema.sql
   ```
   This also creates the low-privilege `portfolio_app` DB user the app
   itself will use — edit the password in `schema.sql` before running it.

## 4. Create the DynamoDB table

From the EC2 instance, after deploying the app code (step 5):

```bash
php dynamodb/create_table_and_seed.php
```

This creates the `SkillsAndCertifications` table (pay-per-request billing,
no capacity planning needed) and loads the sample skills/certifications.

## 5. Deploy the app code

```bash
# on your machine, from inside portfolio-app/
zip -r portfolio-app.zip . -x "vendor/*"
scp -i your-key.pem portfolio-app.zip ec2-user@<EC2_PUBLIC_IP>:~/

# on the EC2 instance
unzip portfolio-app.zip -d portfolio-app
cd portfolio-app
composer install --no-dev

sudo cp -r public/* /var/www/html/
sudo mkdir -p /var/www/config /var/www/vendor
sudo cp -r config/* /var/www/config/
sudo cp -r vendor/* /var/www/vendor/
```

> Keep `config/` and `vendor/` **outside** `/var/www/html` — this avoids
> ever serving those files directly over HTTP. Because of that, open
> `public/academic.php`, `public/skills.php`, `config/db_config.php`, and
> `config/dynamo_config.php`, and update the `require`/`require_once`
> paths to point at `/var/www/config/...` and
> `/var/www/vendor/autoload.php` respectively.

## 6. Set environment variables for the RDS connection

```bash
sudo tee /etc/httpd/conf.d/portfolio-env.conf > /dev/null <<'EOF'
SetEnv RDS_HOST your-instance.xxxxxxxxxx.ap-south-1.rds.amazonaws.com
SetEnv RDS_PORT 3306
SetEnv RDS_DB student_portfolio
SetEnv RDS_USER portfolio_app
SetEnv RDS_PASS CHANGE_ME_STRONG_PASSWORD
SetEnv DYNAMO_REGION ap-south-1
SetEnv DYNAMO_TABLE SkillsAndCertifications
EOF

sudo systemctl restart httpd
```

(`getenv()` in PHP picks these up from `SetEnv` when using `mod_php`.)

## 7. Verify

- `http://<EC2_PUBLIC_IP>/` → landing page with two links
- `http://<EC2_PUBLIC_IP>/academic.php` → table of courses/grades from RDS
- `http://<EC2_PUBLIC_IP>/skills.php` → cards of skills/certifications from DynamoDB

If either page shows the red error box, check `sudo tail -f /var/log/httpd/error_log`
— connection errors are logged server-side and never shown to the browser.

## 8. (Optional) Put it behind a domain / HTTPS

Attach an Elastic IP so the address doesn't change on reboot, and put
CloudFront + ACM or an nginx reverse proxy + Let's Encrypt in front if you
want HTTPS for the report's "active URL."
