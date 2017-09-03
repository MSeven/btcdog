FROM php:7.1-cli
COPY . /usr/src/bitdog
WORKDIR /usr/src/bitdog
CMD [ "php", "./getstats.php" ]