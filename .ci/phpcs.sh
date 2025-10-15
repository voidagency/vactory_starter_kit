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

# Start total timing
TOTAL_START_TIME=$(date +%s)

# Git fetch with timing
GIT_START_TIME=$(date +%s)
print_step "Navigating to Vactory Starter Kit directory" "📂" "\033[36m"
cd /var/www/html/profiles/contrib/vactory_starter_kit

print_step "Fetching git branches: ${DRONE_TARGET_BRANCH} and ${DRONE_SOURCE_BRANCH}" "🔄" "\033[36m"
git fetch origin ${DRONE_TARGET_BRANCH} ${DRONE_SOURCE_BRANCH}
print_step_complete "Git fetch" "✅" "\033[32m" $GIT_START_TIME

# Detect changed files with timing
DETECT_START_TIME=$(date +%s)
print_step "Detecting changed files" "🔍" "\033[35m"

# files=$(git diff --name-status ${DRONE_TARGET_BRANCH} | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | sed -E "s/(A|M|C)[[:space:]]+//g" | sed "s|^|$(pwd)/|")
# files=$(git diff --name-status ${DRONE_TARGET_BRANCH} | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | awk '{print $2}' | sed "s|^|$(pwd)/|")
files=$(git diff --name-status ${DRONE_TARGET_BRANCH} | grep "^[MA]" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | awk '{print $2}' | sed "s|^|$(pwd)/|")

# Count files
file_count=$(echo "$files" | wc -w | tr -d ' ')

if [ -z "$files" ]; then
    echo -e "\033[33m⚠️  No files to check\033[0m"
    print_step_complete "File detection" "✅" "\033[32m" $DETECT_START_TIME
    echo ""
    echo -e "\033[32m🎉 PHPCS check completed - no files to analyze\033[0m"
    exit 0
fi

echo -e "\033[36m📄 Found ${file_count} file(s) to check:\033[0m"
for file in $files; do
    echo -e "   • $(basename $file)"
done
echo ""

print_step_complete "File detection" "✅" "\033[32m" $DETECT_START_TIME

# Run PHPCS with timing
PHPCS_START_TIME=$(date +%s)
print_step "Running PHP Code Sniffer on ${file_count} file(s)" "🔎" "\033[33m"

cd /var/www/html/
php scripts/githooks/phpCodeSniffer.php $files
PHPCS_EXIT_CODE=$?

# Calculate timing
PHPCS_ELAPSED=$(get_elapsed_time $PHPCS_START_TIME)
TOTAL_ELAPSED=$(get_elapsed_time $TOTAL_START_TIME)

echo ""
if [ $PHPCS_EXIT_CODE -eq 0 ]; then
    echo -e "\033[32m🎉 PHPCS check passed! (${PHPCS_ELAPSED})\033[0m"
    echo -e "\033[32m✅ All ${file_count} file(s) comply with coding standards\033[0m"
else
    echo -e "\033[31m❌ PHPCS check failed! (${PHPCS_ELAPSED})\033[0m"
    echo -e "\033[31m💥 Found coding standard violations in ${file_count} file(s)\033[0m"
fi

echo ""
echo -e "\033[36m⏱️  Timing Summary:\033[0m"
echo -e "   • Git Fetch: $(get_elapsed_time $GIT_START_TIME)"
echo -e "   • File Detection: $(get_elapsed_time $DETECT_START_TIME)"
echo -e "   • PHPCS Check: ${PHPCS_ELAPSED}"
echo -e "   • Total: ${TOTAL_ELAPSED}"
echo ""

# Exit with the same code as PHPCS
exit $PHPCS_EXIT_CODE