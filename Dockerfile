FROM php:7.1-cli
COPY . /usr/src/bitdog
WORKDIR /usr/src/bitdog
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install
ENTRYPOINT  [ "php", "./getstats.php" ]