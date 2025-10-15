#!/bin/bash

# Dump environment variables for debugging
echo -e "\033[36m🔍 Environment Variables:\033[0m"
echo -e "   • DRONE_SOURCE_BRANCH: ${DRONE_SOURCE_BRANCH:-'NOT SET'}"
echo -e "   • DRONE_COMMIT: ${DRONE_COMMIT:-'NOT SET'}"
echo -e "   • BITBUCKET_AUTHTOKEN: ${BITBUCKET_AUTHTOKEN:+'SET (hidden)'}${BITBUCKET_AUTHTOKEN:-'NOT SET'}"
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

# Wait for MySQL to be ready
print_step "Waiting for MySQL to be ready" "⏳" "\033[33m"
sleep 3
while ! mysqladmin ping -h ${MYSQL_HOST} --silent; do
    sleep 1
done

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
echo -e "\033[33m🔧 Composer command being executed:\033[0m"
echo "composer require \\"
echo "    weitzman/drupal-test-traits \\"
echo "    drupal/core-dev \\"
echo "    --dev \\"
echo "    --quiet \\"
echo "    --no-progress \\"
echo "    --no-interaction \\"
echo "    --with-all-dependencies \\"
echo "    --update-with-dependencies"
echo ""

if ! composer require \
    weitzman/drupal-test-traits \
    drupal/core-dev \
    --dev \
    --quiet \
    --no-progress \
    --no-interaction \
    --with-all-dependencies \
    --update-with-dependencies; then
    echo -e "\033[31m❌ Error: Failed to install additional packages\033[0m"
    echo -e "\033[31m💥 Check the composer output above for details\033[0m"
    echo -e "\033[33m🔧 To debug manually, run the command above without --no-interaction and --quiet\033[0m"
    echo -e "\033[33m🔍 Also verify DRONE_SOURCE_BRANCH and DRONE_COMMIT values are correct\033[0m"
    if [ "$DEV_MODE" = "true" ]; then
        echo -e "\033[33m🔧 Development mode: keeping container running for debugging\033[0m"
        tail -f /dev/null
    else
        exit 1
    fi
fi

print_step_complete "Composer install" "✅" "\033[32m" $COMPOSER_START_TIME

# Check if phpunit is available
print_step "Verifying phpunit availability" "🔍" "\033[33m"
if [ ! -f "/var/www/html/vendor/bin/phpunit" ]; then
    echo -e "\033[31m❌ Error: phpunit not found at /var/www/html/vendor/bin/phpunit\033[0m"
    echo -e "\033[31m💥 Composer install may have failed or phpunit was not installed\033[0m"
    if [ "$DEV_MODE" = "true" ]; then
        echo -e "\033[33m🔧 Development mode: keeping container running for debugging\033[0m"
        tail -f /dev/null
    else
        exit 1
    fi
fi
echo -e "\033[32m✅ phpunit found and ready\033[0m"

# set environment variables
export IS_DOCKER=true
export DOCKER_DB_NAME=${MYSQL_DATABASE}
export DOCKER_DB_USER=${MYSQL_USER}
export DOCKER_DB_PASSWORD=${MYSQL_PASSWORD}
export DOCKER_DB_HOST=${MYSQL_HOST}
export DOCKER_DB_PORT=3306

# Drupal site setup with timing
DRUPAL_START_TIME=$(date +%s)

print_step "Installing Drupal site" "🏗️" "\033[35m"
/usr/bin/php -d memory_limit=-1 /opt/app-root/src/.config/composer/vendor/bin/drush --root=/var/www/html --db-url=mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@${MYSQL_HOST}:3306/${MYSQL_DATABASE}?module=mysql si vactory_starter_kit

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

# create a new phpunit.xml file
print_step "Creating phpunit.xml file" "🔍" "\033[33m"
cat << EOF > phpunit.xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         bootstrap="core/tests/bootstrap.php" colors="true"
         beStrictAboutTestsThatDoNotTestAnything="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutChangesToGlobalState="true"
         failOnWarning="true"
         printerClass="\Drupal\Tests\Listeners\HtmlOutputPrinter"
         cacheResult="false"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.3/phpunit.xsd">
  <php>
    <ini name="error_reporting" value="32767"/>
    <ini name="memory_limit" value="-1"/>
    <env name="DTT_BASE_URL" value="http://localhost:8080"/>
    <env name="DTT_MINK_DRIVER_ARGS" value='["firefox", null, "http://localhost:4444/wd/hub"]'/>
    <env name="DTT_API_OPTIONS" value='{"socketTimeout": 360, "domWaitTimeout": 3600000}' />
    <env name="BROWSERTEST_OUTPUT_DIRECTORY" value=""/>
  </php>

  <testsuites>
    <testsuite name="unit">
      <directory>./profiles/contrib/vactory_starter_kit/*/tests/src/Unit</directory>
    </testsuite>
    <testsuite name="kernel">
      <directory>./profiles/contrib/vactory_starter_kit/*/tests/src/Kernel</directory>
    </testsuite>
    <testsuite name="existing-site">
      <directory>./profiles/contrib/vactory_starter_kit/*/tests/src/ExistingSite</directory>
    </testsuite>
    <testsuite name="existing-site-javascript">
      <directory>./profiles/contrib/vactory_starter_kit/*/tests/src/ExistingSiteJavascript</directory>
    </testsuite>
  </testsuites>
</phpunit>
EOF

print_step "phpunit.xml file" "🔍" "\033[33m"
cat phpunit.xml

print_step_complete "phpunit.xml file" "✅" "\033[32m" $CONFIG_START_TIME


print_step "Running tests" "🧪" "\033[36m"
export SIMPLETEST_DB="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@${MYSQL_HOST}:3306/${MYSQL_DATABASE}"
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
