#!/bin/bash

# Function to get elapsed time
get_elapsed_time() {
    local start_time=$1
    local end_time=$(date +%s)
    local elapsed=$((end_time - start_time))
    echo "${elapsed}s"
}

# Function to print step with timing
print_step() {
    local step_name="$1"
    local emoji="$2"
    local color="$3"
    echo -e "${color}${emoji} ${step_name}...\033[0m"
}

# Function to print step completion with timing
print_step_complete() {
    local step_name="$1"
    local emoji="$2"
    local color="$3"
    local start_time="$4"
    local elapsed=$(get_elapsed_time $start_time)
    echo -e "${color}${emoji} ${step_name} complete! (${elapsed})\033[0m"
}

# Function to generate secure random strings
generate_random_string() {
    local length=${1:-16}
    openssl rand -base64 $length | tr -d "=+/" | cut -c1-$length
}

# Generate dynamic MySQL configuration values
MYSQL_ROOT_PASSWORD=$(generate_random_string 20)
MYSQL_DATABASE="test_db_$(generate_random_string 8)"
MYSQL_USER="test_user_$(generate_random_string 8)"
MYSQL_PASSWORD=$(generate_random_string 20)

# Check required environment variables
if [ -z "$DRONE_SOURCE_BRANCH" ]; then
    echo "Error: DRONE_SOURCE_BRANCH environment variable is required but not set. Exiting."
    exit 1
fi

if [ -z "$DRONE_COMMIT" ]; then
    echo "Error: DRONE_COMMIT environment variable is required but not set. Exiting."
    exit 1
fi

# Set variables from environment
BRANCH="$DRONE_SOURCE_BRANCH"
COMMIT_HASH="$DRONE_COMMIT"

# Database setup with timing
DB_START_TIME=$(date +%s)

# Clean up any existing MySQL data if corrupted
if [ -d "/var/lib/mysql" ] && [ "$(ls -A /var/lib/mysql)" ]; then
    print_step "Cleaning up existing MySQL data" "🧹" "\033[33m"
    rm -rf /var/lib/mysql/*
fi

# Initialize MySQL data directory
print_step "Initializing MySQL data directory" "🗄️" "\033[36m"
mysqld --initialize-insecure --user=1001 --datadir=/var/lib/mysql --log-error=/var/log/mysqld.log

# Fix ownership after initialization
chown -R 1001:0 /var/lib/mysql
chown -R 1001:0 /var/log

# Start MySQL daemon in background
print_step "Starting MySQL daemon" "🚀" "\033[32m"
mysqld --user=1001 --datadir=/var/lib/mysql --log-error=/var/log/mysqld.log &
MYSQL_PID=$!

# Wait for MySQL to be ready
print_step "Waiting for MySQL to be ready" "⏳" "\033[33m"
sleep 3
while ! mysqladmin ping -h 127.0.0.1 --silent; do
    echo "Waiting for database to be ready..."
    sleep 1
done

# Set root password securely
print_step "Setting MySQL root password" "🔐" "\033[35m"
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null || \
mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SELECT 1;" 2>/dev/null || \
mysqladmin -u root password "${MYSQL_ROOT_PASSWORD}" 2>/dev/null

# Create database and user
print_step "Creating database and user" "👤" "\033[34m"
mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};" 2>/dev/null
mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';" 2>/dev/null
mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';" 2>/dev/null
mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "FLUSH PRIVILEGES;" 2>/dev/null

print_step_complete "Database setup" "✅" "\033[32m" $DB_START_TIME

# echo "MySQL setup complete!"
# echo "Root password: ${MYSQL_ROOT_PASSWORD}"
# echo "Database: ${MYSQL_DATABASE}"
# echo "User: ${MYSQL_USER}"
# echo "User password: ${MYSQL_PASSWORD}"

# Show databases to verify MySQL is working
# echo "Showing databases:"
#mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SHOW DATABASES;"

# Web servers setup with timing
WEB_START_TIME=$(date +%s)

print_step "Starting PHP-FPM" "🚀" "\033[32m"
php-fpm -F > /dev/null 2>&1 &

print_step "Starting Nginx" "🚀" "\033[32m"
nginx -g "daemon off;" > /dev/null 2>&1 &

print_step "Waiting for Nginx and PHP-FPM to be ready" "⏳" "\033[33m"

while ! curl -s http://localhost:8080/fpm-ping > /dev/null 2>&1; do
    echo "Waiting for web servers to be ready..."
    sleep 1
done

print_step_complete "Web servers setup" "✅" "\033[32m" $WEB_START_TIME

# Mock frontend server setup
print_step "Starting mock frontend server" "🎭" "\033[33m"
export BASE_FRONTEND_URL=http://localhost:8085
export FRONTEND_CACHE_KEY="cache_$(generate_random_string 12)"

# Create mock directory and file
mkdir -p /tmp/mock-frontend
cat > /tmp/mock-frontend/index.php << 'EOF'
<?php
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
exit;
?>
EOF

# Start PHP server
php -S localhost:8085 -t /tmp/mock-frontend > /dev/null 2>&1 &

print_step_complete "Mock frontend server" "✅" "\033[32m" $WEB_START_TIME

cd /var/www/html/

# Composer setup with timing
COMPOSER_START_TIME=$(date +%s)

print_step "Installing composer dependencies" "📦" "\033[36m"
composer install --no-progress --no-interaction --quiet
composer config --no-plugins allow-plugins true --quiet

print_step "Installing additional packages" "📦" "\033[36m"
composer require \
    weitzman/drupal-test-traits \
    drupal/core-dev \
    "voidagency/vactory_starter_kit:dev-${BRANCH}#${COMMIT_HASH}" \
    --dev \
    --no-progress \
    --no-interaction \
    --quiet \
    --with-all-dependencies \
    --update-with-dependencies

print_step_complete "Composer install" "✅" "\033[32m" $COMPOSER_START_TIME

# set environment variables
export IS_DOCKER=true
export DOCKER_DB_NAME=${MYSQL_DATABASE}
export DOCKER_DB_USER=${MYSQL_USER}
export DOCKER_DB_PASSWORD=${MYSQL_PASSWORD}
export DOCKER_DB_HOST=localhost
export DOCKER_DB_PORT=3306

# Drupal site setup with timing
DRUPAL_START_TIME=$(date +%s)

print_step "Installing Drupal site" "🏗️" "\033[35m"
/usr/bin/php -d memory_limit=-1 /opt/app-root/src/.config/composer/vendor/bin/drush --root=/var/www/html --db-url=mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@localhost:3306/${MYSQL_DATABASE}?module=mysql si vactory_starter_kit

print_step "Running database updates" "🔄" "\033[35m"
/usr/bin/php -d memory_limit=-1 /opt/app-root/src/.config/composer/vendor/bin/drush updb -y

print_step "Clearing cache" "🧹" "\033[35m"
/usr/bin/php -d memory_limit=-1 /opt/app-root/src/.config/composer/vendor/bin/drush cr

print_step "Adding languages" "🌍" "\033[35m"
/usr/bin/php -d memory_limit=-1 /opt/app-root/src/.config/composer/vendor/bin/drush language:add ar,fr --skip-translations --yes
drush language:info
# /usr/bin/php -d memory_limit=-1 /opt/app-root/src/.config/composer/vendor/bin/drush status

print_step_complete "Drupal site setup" "✅" "\033[32m" $DRUPAL_START_TIME

# System configuration with timing
CONFIG_START_TIME=$(date +%s)

print_step "Hiding system logs" "🔇" "\033[33m"
drush config:set system.logging error_level hide --yes
drush config:set system.logging level none --yes

print_step_complete "System configuration" "✅" "\033[32m" $CONFIG_START_TIME

# Testing with timing
TEST_START_TIME=$(date +%s)

print_step "Running tests" "🧪" "\033[36m"
export SIMPLETEST_DB="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@localhost:3306/${MYSQL_DATABASE}"
/var/www/html/vendor/bin/phpunit --bootstrap=./vendor/weitzman/drupal-test-traits/src/bootstrap.php -c ./phpunit.xml --testdox --verbose --stderr /var/www/html/profiles/contrib/vactory_starter_kit/
TEST_EXIT_CODE=$?

# Calculate test timing
TEST_ELAPSED=$(get_elapsed_time $TEST_START_TIME)

echo ""
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "\033[32m🎉 All tests passed successfully! (${TEST_ELAPSED})\033[0m"
    echo -e "\033[32m✅ Test suite completed with exit code: $TEST_EXIT_CODE\033[0m"
else
    echo -e "\033[31m❌ Tests failed! (${TEST_ELAPSED})\033[0m"
    echo -e "\033[31m💥 Test suite completed with exit code: $TEST_EXIT_CODE\033[0m"
fi

echo ""
echo -e "\033[36m📊 Final Status Summary:\033[0m"
echo -e "   • Database: \033[32m✅ Ready\033[0m"
echo -e "   • Web Servers: \033[32m✅ Running\033[0m"
echo -e "   • Drupal Site: \033[32m✅ Installed\033[0m"
echo -e "   • Tests: $([ $TEST_EXIT_CODE -eq 0 ] && echo -e "\033[32m✅ Passed\033[0m" || echo -e "\033[31m❌ Failed\033[0m")"

echo ""
echo -e "\033[36m⏱️  Timing Summary:\033[0m"
echo -e "   • Database Setup: $(get_elapsed_time $DB_START_TIME)"
echo -e "   • Web Servers: $(get_elapsed_time $WEB_START_TIME)"
echo -e "   • Composer: $(get_elapsed_time $COMPOSER_START_TIME)"
echo -e "   • Drupal Setup: $(get_elapsed_time $DRUPAL_START_TIME)"
echo -e "   • Configuration: $(get_elapsed_time $CONFIG_START_TIME)"
echo -e "   • Testing: ${TEST_ELAPSED}"

# Exit with the same code as the tests
exit $TEST_EXIT_CODE
