# UpstartMonitorBundle
[Symfony](http://symfony.com/what-is-symfony) based web UI for [sfnix/upstart](https://github.com/tarasbogach/UpstartBundle) bundle.
It helps to make any symfony command (or any other script) run forever in background and restart on fails.
##Installation
Require the bundle and its dependencies with composer:
```bash
$ composer require sfnix/upstart_monitor
```
Check for openssl (recommended) or mcrypt PHP extension.
```bash
$ php -i | grep -e "openssl" -e "mcrypt"
```
Register the bundle:
```php
// app/AppKernel.php

public function registerBundles()
{
    //...
    $bundles = [
        //...
        new SfNix\UpstartBundle\UpstartBundle(),
        new SfNix\UpstartMonitorBundle\UpstartMonitorBundle(),
    ];
    //...
}
```
```yml
# app/config/routing.yml
upstart_monitor:
    resource: "@UpstartMonitorBundle/Resources/config/routing.yml"
    prefix:   /upstart 
```
```yml
# app/config/security.yml
security:
    #...
    access_control:
        upstart: { path: ^/upstart, roles: ROLE_ADMIN }
```
```yml
# app/config/config.yml
imports:
    # ...
    - { resource: upstart.yml }
```
```yml
# app/config/upstart.yml
upstart_monitor:
    #WebSocket server to start.
    server:
      host: 0.0.0.0
      port: 13000
    #WebSocket client.
    client:
      schema: 'ws'
      port: 13000
      path: /
upstart:
    #...
    job:
        monitor:
            command: upstart:monitor
            verbose: 1
            native: {respawn: true, setuid: root}
        #...
```
See [sfnix/upstart](https://github.com/tarasbogach/UpstartBundle) for upstart configuration.
```bash
$ ./app/console upstart:install
$ ./app/console upstart:start monitor
```
You can go to http://{your.domain}/upstart
##Warning
UpstartMonitorBundle upstart:monitor command must run with superuser rights to be able to start/stop jobs, so please, be careful!
UpstartMonitorBundle comes with ABSOLUTELY NO WARRANTY, use it at your own risk.