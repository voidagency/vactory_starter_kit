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

# Cleanup with timing
CLEANUP_START_TIME=$(date +%s)
print_step "Cleaning existing files" "🧹" "\033[33m"
rm -rf /var/www/html/*
print_step_complete "Cleanup" "✅" "\033[32m" $CLEANUP_START_TIME

# Clone Vactory8 with timing
VACTORY8_START_TIME=$(date +%s)
print_step "Cloning Vactory8 repository (main branch)" "📥" "\033[36m"
git clone -b main --single-branch --verbose https://x-token-auth:${BITBUCKET_AUTHTOKEN}@bitbucket.org/adminvoid/vactory8.git /var/www/html
print_step_complete "Vactory8 clone" "✅" "\033[32m" $VACTORY8_START_TIME

# Clone Vactory Starter Kit with timing
STARTER_KIT_START_TIME=$(date +%s)
print_step "Cloning Vactory Starter Kit repository" "📥" "\033[36m"
mkdir -p /var/www/html/profiles/contrib/vactory_starter_kit
git clone https://github.com/voidagency/vactory_starter_kit.git /var/www/html/profiles/contrib/vactory_starter_kit

print_step "Checking out commit: ${DRONE_COMMIT}" "🔄" "\033[35m"
cd /var/www/html/profiles/contrib/vactory_starter_kit
git checkout $DRONE_COMMIT
print_step_complete "Vactory Starter Kit clone" "✅" "\033[32m" $STARTER_KIT_START_TIME

cd /var/www/html/

echo -e "\033[36m🔍 Directory contents:\033[0m"
ls -la
echo ""
echo -e "\033[36m🔍 Vactory Starter Kit contents:\033[0m"
ls -la /var/www/html/profiles/contrib/vactory_starter_kit
echo ""

# Composer configuration with timing
COMPOSER_START_TIME=$(date +%s)
print_step "Configuring composer for local development" "⚙️" "\033[35m"

cat > update_composer.php << 'EOF'
<?php
$composer = json_decode(file_get_contents('composer.json'), true);
// Add path repository at the beginning so it takes precedence
array_unshift($composer['repositories'], [
    'type' => 'path',
    'url' => '/var/www/html/profiles/contrib/vactory_starter_kit',
    'options' => ['symlink' => true],
    'canonical' => true
]);
$composer['require']['voidagency/vactory_starter_kit'] = '@dev';
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
EOF

php update_composer.php
rm update_composer.php

echo -e "\033[36m📄 Updated composer.json:\033[0m"
cat composer.json
echo ""

print_step "Clearing composer cache" "🗑️" "\033[33m"
composer clear-cache

print_step "Removing composer.lock to force fresh resolution" "🔄" "\033[33m"
# Remove composer.lock to force fresh resolution with the path repository
rm -f composer.lock

print_step "Installing composer dependencies" "📦" "\033[36m"
composer install -vvv

print_step_complete "Composer configuration" "✅" "\033[32m" $COMPOSER_START_TIME

# Calculate total time
TOTAL_START_TIME=${CLEANUP_START_TIME}
TOTAL_ELAPSED=$(get_elapsed_time $TOTAL_START_TIME)

echo ""
echo -e "\033[36m⏱️  Timing Summary:\033[0m"
echo -e "   • Cleanup: $(get_elapsed_time $CLEANUP_START_TIME)"
echo -e "   • Vactory8 Clone: $(get_elapsed_time $VACTORY8_START_TIME)"
echo -e "   • Starter Kit Clone: $(get_elapsed_time $STARTER_KIT_START_TIME)"
echo -e "   • Composer Setup: $(get_elapsed_time $COMPOSER_START_TIME)"
echo -e "   • Total: ${TOTAL_ELAPSED}"
echo ""
