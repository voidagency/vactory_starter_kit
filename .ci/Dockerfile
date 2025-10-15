FROM vactory/php-ubi9:8.3.14

ENV SONAR_VERSION=7.1.0.4889
#ENV SONAR_VERSION=7.3.0.5189
ENV SONAR_SCANNER_CLI=sonar-scanner-cli-${SONAR_VERSION}
ENV SONAR_SCANNER=sonar-scanner-${SONAR_VERSION}

USER 0
RUN dnf update --nobest -y && dnf install mysql-server java-17-openjdk -y

# Install SonarScanner
RUN curl https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/${SONAR_SCANNER_CLI}.zip -so /opt/${SONAR_SCANNER_CLI}.zip && \
unzip -d /opt /opt/${SONAR_SCANNER_CLI}.zip && \
rm /opt/${SONAR_SCANNER_CLI}.zip
ENV PATH=$PATH:/opt/${SONAR_SCANNER}/bin

# Create MySQL directories and set permissions
RUN mkdir -p /var/lib/mysql /var/log /var/run/mysqld
RUN chown -R 1001:0 /var/lib/mysql /var/log /var/run/mysqld
RUN chmod 755 /var/lib/mysql /var/log /var/run/mysqld

RUN mkdir -p /sonar-cache/
RUN chmod 777 /sonar-cache

RUN mkdir -p /sonar-cache/cache/_tmp
RUN chmod 777 /sonar-cache/cache/_tmp
RUN chown -R 1001:0 /sonar-cache/cache/_tmp

RUN touch /sonar-cache/_tmp
RUN chmod 777 /sonar-cache/_tmp
RUN chown -R 1001:0 /sonar-cache/_tmp


RUN mkdir -p /composer-cache
RUN chmod 777 /composer-cache

RUN chown -R 1001:0 /sonar-cache

RUN chown -R 1001:0 /composer-cache

# Clean up the workspace
RUN rm -rf /var/www/html
RUN mkdir -p /var/www/html
RUN chown -R 1001:0 /var/www/html

RUN yum install -y sudo jq
RUN usermod -aG wheel default
RUN echo "default ALL=(ALL) NOPASSWD:ALL" | tee -a /etc/sudoers.d/default

# Copy the entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

# Copy the phpunit.xml file and the update_composer.php script
COPY phpunit.xml /var/www/phpunit.xml
COPY scripts/update_composer.php /var/www/update_composer.php

# Set the user to the application user
USER 1001

# Set the working directory to the root of the project
WORKDIR /var/www/html/