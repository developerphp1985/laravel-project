# Lendo setup

Tech stack

* PHP 7.1.14 
* MySql
* Laravel Framework
* npm
* docker

You can setup everything on your local machine or just run on docker.

### SSL certificate setup 
In either case you will still need to set up a locall ssl certificate

The application runs on HTTPS.

To run the application you need setup ssl certificate on your local machine

Follow these instructions for creating local ssl certificates `https://gist.github.com/nrollr/4daba07c67adcb30693e`

Make sure that you have files localhost.crt and localhost.key in /etc/apache2/ssl at the end of this process

### Import Dev SQL DB
Also you will need to import the database to your local server

run script `cd sql; sh migrate.sh`

run migration script `php artisan migrate`

### Run using Docker

#### To build docker image
Go to the root directory and then run
1. `build/docker_build.sh`

#### To run docker image
Go to the root directory and then run
1. `build/docker_run.sh`

#### To stop docker image
1. `build/docker_run.sh`

### Running manually without using Docker
#### Create apache virtual host
1. ```sudo -su``` or just use ```sudo```
2.  * Edit the Apache configuration file: `nano /etc/apache2/httpd.conf`
    * Find following line `#Include /private/etc/apache2/extra/httpd-vhosts.conf` comment it and below it add
    `Include /private/etc/apache2/vhosts/*.conf`.
    This configures Apache to include all files ending in `.conf` in the `/private/etc/apache2/vhosts/` directory. Now we need to create this directory.
    * `mkdir /etc/apache2/vhosts` `cd /etc/apache2/vhosts`
    * Create `nano lendo.test.conf` file
    * Example of virtual host 
    
    ```
    <VirtualHost *:80>
        ServerName lendo.test
        DocumentRoot "/applicationPath/ico-web/"
        <Directory "/applicationPath/ico-web">
          AllowOverride All
          Options Indexes MultiViews FollowSymLinks
          Require all granted
        </Directory>
        ErrorLog "/private/var/log/apache2/dummy-host2.example.com-error_log"
        CustomLog "/private/var/log/apache2/dummy-host2.example.com-access_log" common
    </VirtualHost>
    
    <VirtualHost *:443>
        ServerName lendo.test
        DocumentRoot "/applicationPath/ico-web/"
        SSLEngine on
        SSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP:+eNULL
        SSLCertificateFile /etc/apache2/ssl/localhost.crt
        SSLCertificateKeyFile /etc/apache2/ssl/localhost.key
    
        <Directory "/applicationPath/ico-web">
          Options Indexes FollowSymLinks
          AllowOverride All
          Order allow,deny
          Allow from all
          Require all granted
        </Directory>
    
        ErrorLog "/private/var/log/apache2/dummy-host2.example.com-error_log"
        CustomLog "/private/var/log/apache2/dummy-host2.example.com-access_log" common
    </VirtualHost>
    
    ```
    * restart apache server `apachectl restart`
    
### Install node_modules

1. `npm install`
2. `npm run development` will bundle package in development mode

### Test it by running 

1. open `https://localhost/index.php` in your browser
2. log in with one of the following users, password is always 'password':
    1. tester@gmail.com
    1. tester1@gmail.com
    1. tester2@gmail.com
    1. tester3@gmail.com
    1. tester4@gmail.com
    1. bigtester@gmail.com

