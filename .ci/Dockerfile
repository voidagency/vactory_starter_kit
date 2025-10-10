FROM vactory/php-ubi9:8.3.14

ARG DRONE_SOURCE_BRANCH
ARG DRONE_COMMIT
ARG BITBUCKET_AUTHTOKEN

ENV DRONE_SOURCE_BRANCH=${DRONE_SOURCE_BRANCH}
ENV DRONE_COMMIT=${DRONE_COMMIT}
ENV BITBUCKET_AUTHTOKEN=${BITBUCKET_AUTHTOKEN}

RUN if [ -z "$DRONE_SOURCE_BRANCH" ]; then echo 'Docker build arg DRONE_SOURCE_BRANCH must be specified. Exiting.'; exit 1; fi
RUN if [ -z "$DRONE_COMMIT" ]; then echo 'Docker build arg DRONE_COMMIT must be specified. Exiting.'; exit 1; fi
RUN if [ -z "$BITBUCKET_AUTHTOKEN" ]; then echo 'Docker build arg BITBUCKET_AUTHTOKEN must be specified. Exiting.'; exit 1; fi

USER 0
RUN dnf update --nobest -y && dnf install mysql-server -y

# Create MySQL directories and set permissions
RUN mkdir -p /var/lib/mysql /var/log /var/run/mysqld
RUN chown -R 1001:0 /var/lib/mysql /var/log /var/run/mysqld
RUN chmod 755 /var/lib/mysql /var/log /var/run/mysqld

RUN rm -rf /var/www/html
USER 1001
RUN git clone -b main --single-branch https://x-token-auth:${BITBUCKET_AUTHTOKEN}@bitbucket.org/adminvoid/vactory8.git /var/www/html
WORKDIR /var/www/html/
RUN rm -rf profiles/contrib/vactory_starter_kit

WORKDIR /var/www/html/

USER 0
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

COPY phpunit.xml /var/www/html/phpunit.xml
USER 1001
