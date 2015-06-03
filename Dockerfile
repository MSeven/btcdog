FROM ubuntu:14.04
MAINTAINER Martin Griesbach <mgriesbach@gmail.com>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update
RUN apt-get -y upgrade
RUN apt-get -y --no-install-recommends install php5-cli curl php5-curl

RUN rm -f /etc/cron.daily/standard

ADD './' /srv/bitdog

WORKDIR /srv/bitdog

RUN curl -sSk https://getcomposer.org/installer | php
RUN php composer.phar install

CMD ["php", "/srv/bitdog/getstats.php"]
