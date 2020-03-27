FROM wordpress:php7.3-apache

# The highest version of woocommerce supported by the billmate payment gateway is 3.7.0
# If another version is needed set this value as a build-arg when building the image.
ARG WOO_VERSION=3.7.0

# Install wp-cli
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
        && chmod +x wp-cli.phar \
        && mv wp-cli.phar /usr/local/bin/wp

# Download woocommerce plugin
RUN curl -L https://github.com/woocommerce/woocommerce/archive/${WOO_VERSION}.tar.gz -o woo.tar.gz \
        && tar -xvf woo.tar.gz -C /usr/src/wordpress/wp-content/plugins \
        && rm woo.tar.gz

COPY wait-for-it.sh /usr/local/bin/
COPY setup.sh /usr/local/bin/
COPY PublicUrl.php /usr/local/bin/

RUN chmod +x /usr/local/bin/wait-for-it.sh && chmod +x /usr/local/bin/setup.sh

ENTRYPOINT [ "setup.sh" ]
CMD ["apache2-foreground"]
