FROM ablyler/php7ast

WORKDIR /usr/src/app

RUN git clone https://github.com/mrb/phan.git .
RUN git reset --hard 66795a9c2da9cfeccb671df24a09cbfabf64a7d8
COPY engine /usr/src/app/plugins/codeclimate/engine

RUN curl -sS https://getcomposer.org/installer | php && \
    /usr/src/app/composer.phar install --no-dev --optimize-autoloader

RUN adduser -u 9000 -D app
USER app

CMD ["/usr/src/app/plugins/codeclimate/engine"]
