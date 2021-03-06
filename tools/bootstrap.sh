#!/usr/bin/env bash

# Set versions
APACHE_VERSION=2.4.7*
HOST=localhost
PORT=8080
MYSQL_VERSION=5.5
MYSQL_ROOT_PASSWORD=pass
MYSQL_USER=user
MYSQL_USER_PASSWORD=pass
MYSQL_HOST=localhost
MYSQL_DATABASE=fx_bot
PHP_VERSION=7.2

# Export variable to fix "dpkg-preconfigure: unable to re-open..." error
export DEBIAN_FRONTEND=noninteractive

# Add ondrej php repository
LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
apt-get update

# Install basic tools
apt-get install -y vim curl

# Install and configure apache
if ! apache2 -v | grep "Apache/"; then
    apt-get install -y apache2="$APACHE_VERSION"

    # Create symlink from default apache web dir to /app
    if ! [ -L /var/www/html ]; then
        rm -rf /var/www
        mkdir /var/www
        ln -fs /app /var/www/html
    fi

    # Enable mod_rewrite for apache
    a2enmod rewrite

    # Enable mod_headers for apache
    a2enmod headers

    # Create logs directory if not exists
    if ! [ -L /var/www/html/tmp/logs ]; then
        mkdir -p /var/www/html/tmp/logs
    fi

    # Allow usage of .htaccess files inside /var/www/html
    if ! fgrep "/var/www/html" /etc/apache2/apache2.conf; then
        cat >> /etc/apache2/apache2.conf <<EOL
ServerName $HOST
Listen $PORT
<VirtualHost *:$PORT>
    DocumentRoot "/var/www/html/public/"
    <Directory "/var/www/html/public/">
        DirectoryIndex app.php
        AllowOverride All
        Order Allow,Deny
        Allow from All
        Require all granted
    </Directory>
    <Directory "/var/www/html/public/assets/">
        <IfModule mod_rewrite.c>
            RewriteEngine Off
        </IfModule>
    </Directory>
    CustomLog /var/www/html/tmp/logs/access_log vhost_combined
    ErrorLog /var/www/html/tmp/logs/error_log
</VirtualHost>
EOL
    fi

fi

# Install and configure php and modules
if ! php -v | grep "PHP $PHP_VERSION"; then
    apt-get install -y php"$PHP_VERSION" \
        php"$PHP_VERSION"-curl \
        php"$PHP_VERSION"-mysql \
        php"$PHP_VERSION"-gd \
        php"$PHP_VERSION"-mbstring \
        php"$PHP_VERSION"-dom \
        php"$PHP_VERSION"-zip \
        php"$PHP_VERSION"-memcached

    # Display all errors for php
    sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/"$PHP_VERSION"/apache2/php.ini
    sed -i "s/display_errors = .*/display_errors = On/" /etc/php/"$PHP_VERSION"/apache2/php.ini

    # Set php upload tmp directory
    sed -i "s/;upload_tmp_dir =/upload_tmp_dir = \/app\/tmp\/upload/" /etc/php/"$PHP_VERSION"/apache2/php.ini
fi


# Set mysql answers and install mysql-server and mysql-client
if ! mysql -V | grep "Distrib $MYSQL_VERSION"; then
    debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
    debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
    apt-get install -y mysql-server-"$MYSQL_VERSION" mysql-client-"$MYSQL_VERSION"

    # Set key_buffer_size to fix "Using unique option prefix key_buffer instead of key_buffer_size..." warning
    if ! fgrep key_buffer_size /etc/mysql/my.cnf; then
        echo 'key_buffer_size = 16M' | sudo tee -a /etc/mysql/my.cnf
    fi

    # Set up database (note no space after -p)
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOL
CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE CHARACTER SET utf8 COLLATE utf8_general_ci;
GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO $MYSQL_USER@$MYSQL_HOST IDENTIFIED BY '$MYSQL_USER_PASSWORD';
FLUSH PRIVILEGES;
EOL

    if [ $? != "0" ]; then
        echo "[Error]: Database creation failed. Aborting."
        exit 1
    fi
fi

cd /app

# Create tables needed by app
mysql -u "$MYSQL_USER" -p"$MYSQL_USER_PASSWORD" -h $MYSQL_HOST $MYSQL_DATABASE < tools/db.sql

# Restart apache
service apache2 restart

# Install git
apt-get install -y git

# Install composer and run install packages
if ! [ -L /usr/bin/composer ]; then
    curl -Ss https://getcomposer.org/installer | php
    mv composer.phar /usr/bin/composer
    chmod +x /usr/bin/composer
fi
composer install --no-plugins --no-scripts

# Install webpack
curl -sL https://deb.nodesource.com/setup_10.x | sudo -E bash -
apt-get install -y nodejs
npm install webpack webpack-cli --save-dev -g
npm install --save-dev \
    style-loader \
    css-loader \
    sass-loader \
    node-sass \
    mini-css-extract-plugin \
    optimize-css-assets-webpack-plugin \
    file-loader \
    clean-webpack-plugin \
    webpack-merge \
    mini-css-extract-plugin \
    optimize-css-assets-webpack-plugin \
    html-webpack-plugin

# Information for user
echo "[Info] Your project will be accessible via url: http://$HOST:$PORT"
