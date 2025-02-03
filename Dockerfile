FROM vactory/php-ubi9:8.1.11
ARG SOURCE_BRANCH_ID
ARG TARGET_BRANCH_ID
RUN if [ -z "$SOURCE_BRANCH_ID" ]; then echo 'Docker build arg SOURCE_BRANCH_ID must be specified. Exiting.'; exit 1; fi
RUN if [ -z "$TARGET_BRANCH_ID" ]; then echo 'Docker build arg TARGET_BRANCH_ID must be specified. Exiting.'; exit 1; fi

USER 0
RUN rm -rf /var/www/html
RUN git clone https://github.com/voidagency/vactory_starter_kit.git /var/www/html
WORKDIR /var/www/html/
RUN composer install
RUN git checkout $SOURCE_BRANCH_ID
RUN vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
RUN php scripts/githooks/phpCodeSniffer.php $(git diff --name-status ${TARGET_BRANCH_ID}..${SOURCE_BRANCH_ID} | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | sed -E "s/(A|M|C)[[:space:]]+//g")
