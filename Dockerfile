FROM vactory/php-ubi9:8.1.11
ARG COMMIT_ID
ENV CHECK_COMMIT_ID=${COMMIT_ID}
RUN if [ -z "$COMMIT_ID" ]; then echo 'Docker build arg COMMIT_ID must be specified. Exiting.'; exit 1; fi

USER 0
RUN rm -rf /var/www/html
RUN git clone --progress --verbose https://github.com/voidagency/vactory_starter_kit.git /var/www/html
WORKDIR /var/www/html/
RUN composer install -vvv
RUN git checkout "$COMMIT_ID" -b fix/phpcs
RUN vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
RUN php scripts/githooks/phpCodeSniffer.php $(git diff-tree --no-commit-id --name-status -r "${COMMIT_ID}" | grep -E "\.(php|module|install|profile|test|inc|theme|txt|md)$" | sed -E "s/(A|M|C)[[:space:]]+//g")
