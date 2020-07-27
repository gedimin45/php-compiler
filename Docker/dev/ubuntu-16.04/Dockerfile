FROM ubuntu:16.04

RUN mkdir compiler && \
    apt-get update && \
    apt-get -y install git build-essential gdb llvm-4.0-dev clang-4.0 unzip curl libcurl4-openssl-dev autoconf libssl-dev libgd-dev libzip-dev bison re2c libxml2-dev libsqlite3-dev libonig-dev vim clang

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
    apt-get -y install nodejs

#RUN curl -L https://github.com/php/php-src/archive/PHP-7.4.zip -o PHP-7.4.zip && unzip PHP-7.4.zip && mv php-src-PHP-7.4 php
RUN curl -L https://www.php.net/distributions/php-7.4.7.tar.gz -o PHP-7.4.tar.gz && tar xf PHP-7.4.tar.gz && mv php-7.4.7 php

WORKDIR php

RUN ./buildconf && \
    ./configure --disable-cgi --with-ffi --with-openssl --enable-mbstring --with-pcre-jit --with-zlib --enable-bcmath --with-curl --enable-pcntl && \
    make -j16 && \
    make install

WORKDIR /
RUN curl -L https://xdebug.org/files/xdebug-2.9.6.tgz -o xdebug.tar.gz && tar xf xdebug.tar.gz && mv xdebug-2.9.6 xdebug
WORKDIR ../xdebug
RUN phpize && ./configure --enable-xdebug && make && make install

WORKDIR ../php-ast
RUN git clone https://github.com/nikic/php-ast . && \
    phpize && \
    ./configure && \
    make && \
    make install

WORKDIR ../sample_prof
RUN git clone https://github.com/nikic/sample_prof.git . && \
    phpize && \
    ./configure && \
    make && \
    make install

COPY php.ini /usr/local/lib/php.ini

RUN curl --silent --show-error https://getcomposer.org/installer | php -- --no-ansi --install-dir=/usr/local/bin --filename=composer

WORKDIR ../compiler

ENV PHP="/usr/local/bin/php", PHP_7_4="/usr/local/bin/php", PHP_CS_FIXER_IGNORE_ENV="true", COMPOSER_ALLOW_SUPERUSER="1"

CMD ["/bin/bash"]
