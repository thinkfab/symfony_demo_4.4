ARG PHP_EXTENSIONS="apcu pdo_mysql xdebug gd intl"

FROM thecodingmachine/php:7.4-v4-apache

ENV APACHE_DOCUMENT_ROOT=public/ \
    PHP_EXTENSION_XDEBUG=1 \

RUN sudo apt update

## WKHTMLTOPDF
RUN sudo apt install -y wget libxrender1 libxtst6 libxi6 \
    && wget https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz \
    && tar vxf wkhtmltox-0.12.4_linux-generic-amd64.tar.xz \
    && sudo cp wkhtmltox/bin/wkhtmltopdf /usr/bin/ \
    && rm -R wkhtmltox-0.12.4_linux-generic-amd64.tar.xz wkhtmltox/

RUN sudo apt-get clean \
    && sudo rm -rf /var/lib/apt/lists/*
