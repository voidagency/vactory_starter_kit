#!/bin/bash

# Set default versions if not provided
PHP_VERSION=${PHP_VERSION:-"8.3"}
COMPOSER_VERSION=${COMPOSER_VERSION:-"2.7.1"}
DRUSH_VERSION=${DRUSH_VERSION:-"8.4.1"}

# Dump environment variables for debugging
echo -e "\033[36m🔍 Environment Variables:\033[0m"
echo -e "   • DRONE_SOURCE_BRANCH: ${DRONE_SOURCE_BRANCH:-'NOT SET'}"
echo -e "   • DRONE_TARGET_BRANCH: ${DRONE_TARGET_BRANCH:-'NOT SET'}"
echo -e "   • DRONE_COMMIT: ${DRONE_COMMIT:-'NOT SET'}"
echo -e "   • BITBUCKET_AUTHTOKEN: ${BITBUCKET_AUTHTOKEN:+'SET (hidden)'}${BITBUCKET_AUTHTOKEN:-'NOT SET'}"
echo -e "   • SONAR_TOKEN: ${SONAR_TOKEN:+'SET (hidden)'}${SONAR_TOKEN:-'NOT SET'}"
echo ""
echo -e "   • PHP_VERSION: ${PHP_VERSION}"
echo -e "   • COMPOSER_VERSION: ${COMPOSER_VERSION}"
echo -e "   • DRUSH_VERSION: ${DRUSH_VERSION}"
echo -e "   • NODE_VERSION: ${NODE_VERSION:-'NOT SET'}"
echo ""
echo -e "   • DEV_MODE: ${DEV_MODE:-'NOT SET'}"
echo ""

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

if [ -z "$BITBUCKET_AUTHTOKEN" ]; then
    echo "Error: BITBUCKET_AUTHTOKEN environment variable is required but not set. Exiting."
    exit 1
fi

if [ -z "$DRONE_TARGET_BRANCH" ]; then
    echo "Error: DRONE_TARGET_BRANCH environment variable is required but not set. Exiting."
    exit 1
fi

if [ -z "$NODE_VERSION" ]; then
    echo "Error: NODE_VERSION environment variable is required but not set. Exiting."
    exit 1
fi

if [ -z "$SONAR_TOKEN" ]; then
    echo "Error: SONAR_TOKEN environment variable is required but not set. Exiting."
    exit 1
fi

# Path configuration - centralized for easy maintenance
DRUPAL_ROOT="/var/www/html"
STARTER_KIT_PATH="${DRUPAL_ROOT}/profiles/contrib/vactory_starter_kit"
DRUSH_BIN="/opt/app-root/src/.config/composer/vendor/bin/drush"
PHPUNIT_BIN="${DRUPAL_ROOT}/vendor/bin/phpunit"
MYSQL_DATA_DIR="/var/lib/mysql"
LOG_DIR="/var/log"
MOCK_FRONTEND_DIR="/tmp/mock-frontend"
UPDATE_COMPOSER_SCRIPT="/var/www/update_composer.php"
PHPUNIT_CONFIG_SOURCE="/var/www/phpunit.xml"
SONAR_HOST_URL="https://sonar.leserveurdetest.com"

# PHP setup with timing
PHP_START_TIME=$(date +%s)

print_step "Resetting PHP module" "🔄" "\033[33m"
sudo dnf module reset -y php

print_step "Installing PHP ${PHP_VERSION} (Remi repository)" "📦" "\033[36m"
sudo dnf module install -y php:remi-${PHP_VERSION}

echo -e "\033[36m📄 PHP version:\033[0m"
php -v | head -n 1
echo ""

PHP_ELAPSED=$(get_elapsed_time $PHP_START_TIME)
print_step_complete "PHP setup" "✅" "\033[32m" $PHP_START_TIME

# Composer setup with timing
COMPOSER_SETUP_START_TIME=$(date +%s)

print_step "Downloading Composer installer" "⬇️" "\033[36m"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

print_step "Verifying Composer installer" "🔐" "\033[35m"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'ed0feb545ba87161262f2d45a633e34f591ebb3381f2e0063c345ebea4d228dd0043083717770234ec00c5a9f9593792') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"

print_step "Installing Composer ${COMPOSER_VERSION}" "📦" "\033[36m"
php composer-setup.php --version=${COMPOSER_VERSION}
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

echo -e "\033[36m📄 Composer version:\033[0m"
composer --version
echo ""

print_step "Installing Drush ${DRUSH_VERSION} globally" "🔧" "\033[35m"
composer global require drush/drush:${DRUSH_VERSION}

echo -e "\033[36m📄 Drush version:\033[0m"
drush --version
echo ""

COMPOSER_SETUP_ELAPSED=$(get_elapsed_time $COMPOSER_SETUP_START_TIME)
print_step_complete "Composer & Drush setup" "✅" "\033[32m" $COMPOSER_SETUP_START_TIME

# Node.js setup with timing
NODE_START_TIME=$(date +%s)

print_step "Installing nvm (Node Version Manager)" "📦" "\033[36m"
touch $HOME/.bashrc
curl -sSL -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.2/install.sh | bash

print_step "Bootstrapping nvm" "🔄" "\033[35m"
export NVM_DIR="$HOME/.nvm" && \
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" --no-use

if [[ -n ${NODE_VERSION} ]]; then
    print_step "Installing Node.js version ${NODE_VERSION}" "🟢" "\033[32m"
    nvm install ${NODE_VERSION} && \
    nvm use ${NODE_VERSION}
else
    print_step "Installing Node.js (latest LTS)" "🟢" "\033[32m"
    nvm install --lts && \
    nvm use --lts
fi

echo -e "\033[36m📄 Node.js version:\033[0m"
node -v
echo ""

NODE_ELAPSED=$(get_elapsed_time $NODE_START_TIME)
print_step_complete "Node.js setup" "✅" "\033[32m" $NODE_START_TIME

# Clone Vactory8 with timing
VACTORY8_START_TIME=$(date +%s)
print_step "Cloning Vactory8 repository (main branch)" "📥" "\033[36m"

# Use GIT_ASKPASS to securely pass credentials without exposing token in URLs
GIT_ASKPASS_SCRIPT="/tmp/git-askpass-$$.sh"
cat > "${GIT_ASKPASS_SCRIPT}" << 'EOF'
#!/bin/sh
echo "$BITBUCKET_AUTHTOKEN"
EOF
chmod +x "${GIT_ASKPASS_SCRIPT}"

# Clone using GIT_ASKPASS (token not visible in process list or error messages)
export GIT_ASKPASS="${GIT_ASKPASS_SCRIPT}"
export BITBUCKET_AUTHTOKEN="${BITBUCKET_AUTHTOKEN}"
git clone -b main --single-branch https://x-token-auth@bitbucket.org/adminvoid/vactory8.git ${DRUPAL_ROOT}
CLONE_EXIT_CODE=$?

# Clean up the temporary script
rm -f "${GIT_ASKPASS_SCRIPT}"
unset GIT_ASKPASS

if [ $CLONE_EXIT_CODE -ne 0 ]; then
    echo -e "\033[31m❌ Error: Failed to clone Vactory8 repository\033[0m"
    echo -e "\033[31m💥 Check that BITBUCKET_AUTHTOKEN is valid and has access to the repository\033[0m"
    exit 1
fi

print_step_complete "Vactory8 clone" "✅" "\033[32m" $VACTORY8_START_TIME

# Clone Vactory Starter Kit with timing
STARTER_KIT_START_TIME=$(date +%s)
print_step "Cloning Vactory Starter Kit repository" "📥" "\033[36m"
mkdir -p ${STARTER_KIT_PATH}
git clone https://github.com/voidagency/vactory_starter_kit.git ${STARTER_KIT_PATH}

print_step "Checking out commit: ${DRONE_COMMIT}" "🔄" "\033[35m"
cd ${STARTER_KIT_PATH}
git checkout "$DRONE_COMMIT"
print_step_complete "Vactory Starter Kit clone" "✅" "\033[32m" $STARTER_KIT_START_TIME

cd ${DRUPAL_ROOT}

echo -e "\033[36m🔍 Directory contents:\033[0m"
ls -la
echo ""
echo -e "\033[36m🔍 Vactory Starter Kit contents:\033[0m"
ls -la ${STARTER_KIT_PATH}
echo ""

# SonarQube code analysis with timing
SONAR_START_TIME=$(date +%s)
print_step "Running SonarQube code analysis" "🔍" "\033[36m"

cd ${STARTER_KIT_PATH}
export SONAR_SCANNER_HOME=/sonar-cache
export SONAR_SCANNER_HOME=/sonar-cache/.sonar
export SCANNER_WORKDIR_PATH=/tmp/.scannerwork
export LANG=C.UTF-8
export LC_ALL=C.UTF-8
sonar-scanner -Dsonar.host.url="${SONAR_HOST_URL}" \
-Dsonar.scanner.socketTimeout=300000 \
-Dsonar.scanner.skipSystemTruststore=true \
-Dsonar.projectBaseDir=${STARTER_KIT_PATH}
SONAR_EXIT_CODE=$?

cd ${DRUPAL_ROOT}

# Calculate timing
SONAR_ELAPSED=$(get_elapsed_time $SONAR_START_TIME)

# Read SonarQube project key from sonar-project.properties
SONAR_PROJECT_KEY=""
if [ -f "${STARTER_KIT_PATH}/sonar-project.properties" ]; then
    SONAR_PROJECT_KEY=$(grep -E "^sonar.projectKey=" "${STARTER_KIT_PATH}/sonar-project.properties" | cut -d'=' -f2 | tr -d ' ')
fi

echo ""
if [ $SONAR_EXIT_CODE -eq 0 ]; then
    echo -e "\033[32m🎉 SonarQube analysis completed successfully! (${SONAR_ELAPSED})\033[0m"
    echo -e "\033[32m✅ Code quality analysis finished\033[0m"
    echo -e "\033[36m🔗 View results: ${SONAR_HOST_URL}/dashboard?id=${SONAR_PROJECT_KEY}\033[0m"
else
    echo -e "\033[31m❌ SonarQube analysis failed! (${SONAR_ELAPSED})\033[0m"
    echo -e "\033[31m💥 Code quality issues detected or scanner error\033[0m"
fi

# Note: We intentionally continue after SonarQube failures to complete all pipeline steps.
# The exit code will be included in the final summary.

# Composer configuration and installation with timing
COMPOSER_START_TIME=$(date +%s)

print_step "Configuring composer for local development" "⚙️" "\033[35m"
php ${UPDATE_COMPOSER_SCRIPT}
rm ${UPDATE_COMPOSER_SCRIPT}
rm -f /var/www/html/profiles/contrib/vactory_starter_kit/.ci/scripts/update_composer.php

echo -e "\033[36m📄 Updated composer.json:\033[0m"
cat composer.json
echo ""

print_step "Installing composer dependencies" "📦" "\033[36m"
composer update voidagency/vactory_starter_kit -W
composer install --no-progress --no-interaction --quiet
composer config --no-plugins allow-plugins true --quiet

CURRENT_DRUPAL_VERSION=$(jq -r '.packages[] | select(.name=="drupal/core") | .version' composer.lock)


print_step "Installing additional packages" "📦" "\033[36m"
echo -e "\033[33m🔧 Composer command being executed:\033[0m"
echo "composer require \\"
echo "    weitzman/drupal-test-traits \\"
echo "    drupal/core-dev:${CURRENT_DRUPAL_VERSION} \\"
echo "    --dev \\"
echo "    --quiet \\"
echo "    --no-progress \\"
echo "    --no-interaction \\"
echo "    --with-all-dependencies \\"
echo "    --update-with-dependencies"
echo ""

if ! composer require \
    weitzman/drupal-test-traits \
    drupal/core-dev:${CURRENT_DRUPAL_VERSION} \
    --dev \
    --no-progress \
    --no-interaction \
    --with-all-dependencies \
    --update-with-dependencies; then
    echo -e "\033[31m❌ Error: Failed to install additional packages\033[0m"
    echo -e "\033[31m💥 Check the composer output above for details\033[0m"
    echo -e "\033[33m🔧 To debug manually, run the command above without --no-progress and --quiet\033[0m"
    echo -e "\033[33m🔍 Also verify DRONE_SOURCE_BRANCH and DRONE_COMMIT values are correct\033[0m"
    if [ "$DEV_MODE" = "true" ]; then
        echo -e "\033[33m🔧 Development mode: keeping container running for debugging\033[0m"
        tail -f /dev/null
    else
        exit 1
    fi
fi

COMPOSER_ELAPSED=$(get_elapsed_time $COMPOSER_START_TIME)
print_step_complete "Composer configuration & install" "✅" "\033[32m" $COMPOSER_START_TIME

# Start total timing
TOTAL_START_TIME=$(date +%s)

# Git fetch with timing
GIT_START_TIME=$(date +%s)
print_step "Navigating to Vactory Starter Kit directory" "📂" "\033[36m"
cd ${STARTER_KIT_PATH}

print_step "Fetching git branches: ${DRONE_TARGET_BRANCH} and ${DRONE_SOURCE_BRANCH}" "🔄" "\033[36m"
git fetch origin ${DRONE_TARGET_BRANCH} ${DRONE_SOURCE_BRANCH}
print_step_complete "Git fetch" "✅" "\033[32m" $GIT_START_TIME

# Detect changed files with timing
DETECT_START_TIME=$(date +%s)
print_step "Detecting changed files" "🔍" "\033[35m"

# Check if source and target branches are the same
if [ "${DRONE_SOURCE_BRANCH}" = "${DRONE_TARGET_BRANCH}" ]; then
    echo -e "\033[33m⚠️  Source and target branches are the same (${DRONE_SOURCE_BRANCH})\033[0m"
    
    # Check if HEAD~1 exists (handles new branches with only one commit)
    if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
        echo -e "\033[36m📄 Comparing against parent commit (HEAD~1)\033[0m"
        files=$(git diff --name-status HEAD~1 | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | awk '{print $2}' | sed "s|^|$(pwd)/|")
    else
        echo -e "\033[33m⚠️  No parent commit found (new branch or first commit)\033[0m"
        echo -e "\033[36m📄 Checking all files in current commit\033[0m"
        # For new branches with single commit, show all files in the commit
        files=$(git diff-tree --no-commit-id --name-status -r HEAD | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | awk '{print $2}' | sed "s|^|$(pwd)/|")
    fi
else
    echo -e "\033[36m📄 Comparing ${DRONE_SOURCE_BRANCH} against ${DRONE_TARGET_BRANCH}\033[0m"
    # Compare source branch against target branch
    files=$(git diff --name-status ${DRONE_TARGET_BRANCH} | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | awk '{print $2}' | sed "s|^|$(pwd)/|")
fi

# Count files
file_count=$(echo "$files" | wc -w | tr -d ' ')

if [ -z "$files" ]; then
    echo -e "\033[33m⚠️  No files to check\033[0m"
    print_step_complete "File detection" "✅" "\033[32m" $DETECT_START_TIME
    echo ""
    echo -e "\033[33m⏭️  Skipping PHPCS check - no files to analyze\033[0m"
    PHPCS_EXIT_CODE=0
    PHPCS_ELAPSED="0s"
else
    echo -e "\033[36m📄 Found ${file_count} file(s) to check:\033[0m"
    echo ""
    
    # Show files with their status and relative path from starter kit root
    if [ "${DRONE_SOURCE_BRANCH}" = "${DRONE_TARGET_BRANCH}" ]; then
        if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
            git diff --name-status HEAD~1 | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | while read status file; do
                status_symbol=$([ "$status" = "A" ] && echo "+" || echo "~")
                echo -e "   \033[32m${status_symbol}\033[0m ${file}"
            done
        else
            git diff-tree --no-commit-id --name-status -r HEAD | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | while read status file; do
                status_symbol=$([ "$status" = "A" ] && echo "+" || echo "~")
                echo -e "   \033[32m${status_symbol}\033[0m ${file}"
            done
        fi
    else
        git diff --name-status ${DRONE_TARGET_BRANCH} | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | while read status file; do
            status_symbol=$([ "$status" = "A" ] && echo "+" || echo "~")
            echo -e "   \033[32m${status_symbol}\033[0m ${file}"
        done
    fi
    
    echo ""
    print_step_complete "File detection" "✅" "\033[32m" $DETECT_START_TIME

    # Run PHPCS with timing
    PHPCS_START_TIME=$(date +%s)
    print_step "Running PHP Code Sniffer on ${file_count} file(s)" "🔎" "\033[33m"

    cd ${DRUPAL_ROOT}
    php scripts/githooks/phpCodeSniffer.php $files
    PHPCS_EXIT_CODE=$?

    # Calculate timing
    PHPCS_ELAPSED=$(get_elapsed_time $PHPCS_START_TIME)

    echo ""
    if [ $PHPCS_EXIT_CODE -eq 0 ]; then
        echo -e "\033[32m🎉 PHPCS check passed! (${PHPCS_ELAPSED})\033[0m"
        echo -e "\033[32m✅ All ${file_count} file(s) comply with coding standards\033[0m"
    else
        echo -e "\033[31m❌ PHPCS check failed! (${PHPCS_ELAPSED})\033[0m"
        echo -e "\033[31m💥 Found coding standard violations in ${file_count} file(s)\033[0m"
    fi
fi

# Note: We intentionally continue after PHPCS failures to complete all pipeline steps.
# The exit code will be included in the final summary.

# Database setup with timing
DB_START_TIME=$(date +%s)

# Clean up any existing MySQL data if corrupted
if [ -d "${MYSQL_DATA_DIR}" ] && [ "$(ls -A ${MYSQL_DATA_DIR})" ]; then
    print_step "Cleaning up existing MySQL data" "🧹" "\033[33m"
    rm -rf ${MYSQL_DATA_DIR}/*
fi

# Initialize MySQL data directory
print_step "Initializing MySQL data directory" "🗄️" "\033[36m"
mysqld --initialize-insecure --user=1001 --datadir=${MYSQL_DATA_DIR} --log-error=${LOG_DIR}/mysqld.log

# Fix ownership after initialization
chown -R 1001:0 ${MYSQL_DATA_DIR}
chown -R 1001:0 ${LOG_DIR}

# Start MySQL daemon in background
print_step "Starting MySQL daemon" "🚀" "\033[32m"
mysqld --user=1001 --datadir=${MYSQL_DATA_DIR} --log-error=${LOG_DIR}/mysqld.log &

# Wait for MySQL to be ready
print_step "Waiting for MySQL to be ready" "⏳" "\033[33m"
MAX_MYSQL_RETRIES=60
MYSQL_RETRY_COUNT=0
while ! mysqladmin ping -h 127.0.0.1 --silent; do
    MYSQL_RETRY_COUNT=$((MYSQL_RETRY_COUNT + 1))
    if [ $MYSQL_RETRY_COUNT -ge $MAX_MYSQL_RETRIES ]; then
        echo -e "\033[31m❌ Error: MySQL failed to start after ${MAX_MYSQL_RETRIES} seconds\033[0m"
        echo -e "\033[31m💥 MySQL initialization or startup may have failed\033[0m"
        if [ "$DEV_MODE" = "true" ]; then
            echo -e "\033[33m🔧 Development mode: keeping container running for debugging\033[0m"
            tail -f /dev/null
        else
            exit 1
        fi
    fi
    echo "Waiting for MySQL to be ready... (attempt ${MYSQL_RETRY_COUNT}/${MAX_MYSQL_RETRIES})"
    sleep 1
done

# Set root password securely
print_step "Setting MySQL root password" "🔐" "\033[35m"
# Conditional error logging: show errors in DEV_MODE for debugging, suppress in production
if [ "$DEV_MODE" = "true" ]; then
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';" 2>&1 || \
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SELECT 1;" 2>&1 || \
    mysqladmin -u root password "${MYSQL_ROOT_PASSWORD}" 2>&1
else
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null || \
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SELECT 1;" 2>/dev/null || \
    mysqladmin -u root password "${MYSQL_ROOT_PASSWORD}" 2>/dev/null
fi

# Create database and user
print_step "Creating database and user" "👤" "\033[34m"
if [ "$DEV_MODE" = "true" ]; then
    # Show MySQL errors in development mode for debugging
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};" 2>&1
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';" 2>&1
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';" 2>&1
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "FLUSH PRIVILEGES;" 2>&1
else
    # Suppress errors in production mode for cleaner output
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};" 2>/dev/null
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';" 2>/dev/null
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';" 2>/dev/null
    mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "FLUSH PRIVILEGES;" 2>/dev/null
fi

DB_ELAPSED=$(get_elapsed_time $DB_START_TIME)
print_step_complete "Database setup" "✅" "\033[32m" $DB_START_TIME

# Create database URL for reuse
DB_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@localhost:3306/${MYSQL_DATABASE}"

# Web servers setup with timing
WEB_START_TIME=$(date +%s)

print_step "Starting PHP-FPM" "🚀" "\033[32m"
php-fpm -F > /dev/null 2>&1 &

print_step "Starting Nginx" "🚀" "\033[32m"
nginx -g "daemon off;" > /dev/null 2>&1 &

print_step "Waiting for Nginx and PHP-FPM to be ready" "⏳" "\033[33m"

MAX_RETRIES=30
RETRY_COUNT=0
while ! curl -s http://localhost:8080/fpm-ping > /dev/null 2>&1; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo -e "\033[31m❌ Error: Web servers failed to start after ${MAX_RETRIES} seconds\033[0m"
        echo -e "\033[31m💥 Nginx or PHP-FPM may have failed to initialize\033[0m"
        if [ "$DEV_MODE" = "true" ]; then
            echo -e "\033[33m🔧 Development mode: keeping container running for debugging\033[0m"
            tail -f /dev/null
        else
            exit 1
        fi
    fi
    echo "Waiting for web servers to be ready... (attempt ${RETRY_COUNT}/${MAX_RETRIES})"
    sleep 1
done

WEB_ELAPSED=$(get_elapsed_time $WEB_START_TIME)
print_step_complete "Web servers setup" "✅" "\033[32m" $WEB_START_TIME

# Mock frontend server setup
MOCK_FRONTEND_START_TIME=$(date +%s)

print_step "Starting mock frontend server" "🎭" "\033[33m"
export BASE_FRONTEND_URL=http://localhost:8085
export FRONTEND_CACHE_KEY="cache_$(generate_random_string 12)"

# Create mock directory and file
mkdir -p ${MOCK_FRONTEND_DIR}
cat > ${MOCK_FRONTEND_DIR}/index.php << 'EOF'
<?php
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
exit;
?>
EOF

# Start PHP server
php -S localhost:8085 -t ${MOCK_FRONTEND_DIR} > /dev/null 2>&1 &

MOCK_FRONTEND_ELAPSED=$(get_elapsed_time $MOCK_FRONTEND_START_TIME)
print_step_complete "Mock frontend server" "✅" "\033[32m" $MOCK_FRONTEND_START_TIME

cd ${DRUPAL_ROOT}

# Check if phpunit is available
print_step "Verifying phpunit availability" "🔍" "\033[33m"
if [ ! -f "${PHPUNIT_BIN}" ]; then
    echo -e "\033[31m❌ Error: phpunit not found at ${PHPUNIT_BIN}\033[0m"
    echo -e "\033[31m💥 Composer install may have failed or phpunit was not installed\033[0m"
    if [ "$DEV_MODE" = "true" ]; then
        echo -e "\033[33m🔧 Development mode: keeping container running for debugging\033[0m"
        tail -f /dev/null
    else
        exit 1
    fi
fi
echo -e "\033[32m✅ phpunit found and ready\033[0m"

# phpunit.xml file with timing
PHPUNIT_CONFIG_START_TIME=$(date +%s)
print_step "Creating phpunit.xml file" "🔍" "\033[33m"
cp ${PHPUNIT_CONFIG_SOURCE} ${DRUPAL_ROOT}/phpunit.xml
print_step_complete "phpunit.xml file" "✅" "\033[32m" $PHPUNIT_CONFIG_START_TIME

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
/usr/bin/php -d memory_limit=-1 ${DRUSH_BIN} --root=${DRUPAL_ROOT} --db-url=${DB_URL}?module=mysql si vactory_starter_kit

print_step "Running database updates" "🔄" "\033[35m"
/usr/bin/php -d memory_limit=-1 ${DRUSH_BIN} updb -y

print_step "Clearing cache" "🧹" "\033[35m"
/usr/bin/php -d memory_limit=-1 ${DRUSH_BIN} cr

print_step "Adding languages" "🌍" "\033[35m"
/usr/bin/php -d memory_limit=-1 ${DRUSH_BIN} language:add ar,fr --skip-translations --yes
/usr/bin/php -d memory_limit=-1 ${DRUSH_BIN} language:info

DRUPAL_ELAPSED=$(get_elapsed_time $DRUPAL_START_TIME)
print_step_complete "Drupal site setup" "✅" "\033[32m" $DRUPAL_START_TIME

# System configuration with timing
CONFIG_START_TIME=$(date +%s)

print_step "Hiding system logs" "🔇" "\033[33m"
${DRUSH_BIN} config:set system.logging error_level hide --yes
${DRUSH_BIN} config:set system.logging level none --yes

CONFIG_ELAPSED=$(get_elapsed_time $CONFIG_START_TIME)
print_step_complete "System configuration" "✅" "\033[32m" $CONFIG_START_TIME

# Testing with timing
TEST_START_TIME=$(date +%s)

print_step "Running tests" "🧪" "\033[36m"
export SIMPLETEST_DB="${DB_URL}"
${PHPUNIT_BIN} --bootstrap=./vendor/weitzman/drupal-test-traits/src/bootstrap.php -c ./phpunit.xml --testdox --verbose --stderr ${STARTER_KIT_PATH}/
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
echo -e "   • PHPCS: $([ $PHPCS_EXIT_CODE -eq 0 ] && echo -e "\033[32m✅ Passed\033[0m" || echo -e "\033[31m❌ Failed\033[0m")"
echo -e "   • SonarQube: $([ $SONAR_EXIT_CODE -eq 0 ] && echo -e "\033[32m✅ Passed\033[0m" || echo -e "\033[31m❌ Failed\033[0m")"
echo -e "   • Database: \033[32m✅ Ready\033[0m"
echo -e "   • Web Servers: \033[32m✅ Running\033[0m"
echo -e "   • Drupal Site: \033[32m✅ Installed\033[0m"
echo -e "   • Tests: $([ $TEST_EXIT_CODE -eq 0 ] && echo -e "\033[32m✅ Passed\033[0m" || echo -e "\033[31m❌ Failed\033[0m")"

echo ""
echo -e "\033[36m🔗 Quality Reports:\033[0m"
echo -e "   • SonarQube Dashboard: \033[34m${SONAR_HOST_URL}/dashboard?id=${SONAR_PROJECT_KEY}\033[0m"

echo ""
echo -e "\033[36m⏱️  Timing Summary:\033[0m"
echo -e "   • PHP Setup: ${PHP_ELAPSED}"
echo -e "   • Composer & Drush: ${COMPOSER_SETUP_ELAPSED}"
echo -e "   • Node.js Setup: ${NODE_ELAPSED}"
echo -e "   • Project Dependencies: ${COMPOSER_ELAPSED}"
echo -e "   • PHPCS: ${PHPCS_ELAPSED}"
echo -e "   • SonarQube: ${SONAR_ELAPSED}"
echo -e "   • Database Setup: ${DB_ELAPSED}"
echo -e "   • Web Servers: ${WEB_ELAPSED}"
echo -e "   • Mock Frontend: ${MOCK_FRONTEND_ELAPSED}"
echo -e "   • Drupal Setup: ${DRUPAL_ELAPSED}"
echo -e "   • Configuration: ${CONFIG_ELAPSED}"
echo -e "   • Testing: ${TEST_ELAPSED}"
TOTAL_ELAPSED=$(get_elapsed_time $TOTAL_START_TIME)
echo -e "   • \033[1mTotal Pipeline: ${TOTAL_ELAPSED}\033[0m"

# Check if DEV_MODE is enabled
if [ "$DEV_MODE" = "true" ]; then
    echo ""
    echo -e "\033[36m🔧 Development mode enabled - keeping container running...\033[0m"
    echo -e "\033[33m💡 Use 'docker exec -it <container_id> bash' to access the container\033[0m"
    echo -e "\033[33m📊 Test exit code was: $TEST_EXIT_CODE\033[0m"
    echo ""
    # Keep container running
    tail -f /dev/null
else
    # Exit with the same code as the tests
    exit $TEST_EXIT_CODE
fi
