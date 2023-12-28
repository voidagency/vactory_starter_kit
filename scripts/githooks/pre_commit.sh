red=`tput setaf 9`
green=`tput setaf 10`
yellow=`tput setaf 11`
cyan=`tput setaf 14`
gray=`tput setaf 7`
reset=`tput sgr0`
pink=`tput setaf 13`
# Get staged files that have modified|added|conflict status then filter by php extensions.
files=($(git diff --cached --name-status --diff-filter=ACM | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | sed -E "s/(A|M|C)[[:space:]]+//g"));
# Set PHPCS Standard to Drupla.
output=`vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer`;
# Apply phpcs to staged php files.
# Validate translation interface t function context.
echo ${yellow};
vendor/squizlabs/php_codesniffer/bin/phpcs --standard=scripts/githooks/lint/InterfaceTranslationStandard --extensions=module,php,inc ${files[*]} && (echo ${reset}; exit 0) || (echo ${reset}; exit 1)
# Validate Drupal coding standards.
echo ${red};
php scripts/githooks/phpCodeSniffer.php ${files[*]} && (echo ${yellow};php scripts/githooks/lint/Blocks.php ${files[*]} && (echo ${reset}; exit 0) || (echo ${reset}; exit 1)) || (echo ${reset}; exit 1)
