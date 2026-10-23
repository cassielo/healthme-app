FROM python:3.12-slim-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    libapache2-mod-php \
    php-mysql \
    && rm -rf /var/lib/apt/lists/* /var/www/html/index.html

COPY recommender/requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir -r /tmp/requirements.txt

# Math libraries on one thread: extra threads only spin on Render's 0.1 CPU
ENV OMP_NUM_THREADS=1 OPENBLAS_NUM_THREADS=1 MKL_NUM_THREADS=1

COPY docker/errors.ini /etc/php/8.2/apache2/conf.d/errors.ini

# Logs go to the container output (docker logs, Render's log page)
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
    && ln -sf /proc/self/fd/2 /var/log/apache2/error.log \
    && ln -sf /proc/self/fd/1 /var/log/apache2/other_vhosts_access.log

# Listen on $PORT: 80 locally, 10000 on Render
ENV PORT=80
RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Server name, so Apache does not warn at every start
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Render's secret files (the database CA certificate) are readable by group 1000: add Apache's user to it
RUN groupadd --gid 1000 render-secrets && usermod --append --groups render-secrets www-data

# Only public/ is served; the rest of the project stays out of reach
RUN sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf

# The code itself (locally, docker-compose mounts the working copy over it)
COPY . /var/www/html

CMD ["apache2ctl", "-D", "FOREGROUND"]
