# ft4a
#### Xbtt tracker front-end in PHP + MySQL + PDO

### Prerequisites
- Nginx
- PHP 7.+
- MySQL server
- Xbt Tracker installed and running (https://github.com/citizenz7/xbt)

### What is it?
This is a front-end to XBT tracker:
- it's a blog. Each torrent is a post with small intro (description), main text (content), media links, post category, post licence, post images, comments, leechers and seeders stats, views count, etc.
- you have to create an account and activate it (as you should receive an e-mail with activation link) then login. Everyone needs an account to keep personnal tracker stats (ratio)
- you can recover your password
- as a member, you can propose new posts (torrents) from the right side main user Menu (Upload page). You'll be able to edit, delete the post
- you may send internal messages to other member
- you have a torrent list with search, member list with search, torrents stats, blog (news from opensource world...), contact form and admin part.

This code is running on https://www.ft4a.fr.

### About XBTT tracker
/!\ A major update has just been released a few days ago (late december 2019) in official Github XBT repo. **Don't use files from official repo** or this front-end will not work. Please use my repo with "old stable" version: https://github.com/citizenz7/xbt

### Site settings
#### CONFIG:
- General site settings are in /web/includes/config.php
- details and comments are in french in mots files
- you MUST change some important infos in config.php and put your own settings (site name, site URL = http + https, paths, announce port, mail, etc.)
- You should not change anything below $ANNOUNCEURL part in config.php. Default settings should be fine.
- First member (with ID #1) is admin and can access admin part of the site. See right menu for links.
- Admin part : Torrents list (edit, delete), Categories list (edit, delete, add), Licenses list (edit, delete, add), Members list (edit, delete, add), Infos (edit, delete, add), Message to all members, logs
- Vers. 2.2+ add a Blog page with News and you can now add/edit News from Admin section.

### Nginx settings
You need a "rewrite" part in Nginx virtualhost file like this :
```
rewrite ^/c-(.*)$ /catpost.php?id=$1 last;
rewrite ^/l-(.*)$ /licpost.php?id=$1 last;
rewrite ^/a-(.*)-(.*)$ /archives.php?month=$1&year=$2 last;

### vers. 2.2+: infos & blog
rewrite ^/i-(.*)$ /infos.php?id=$1 last;

if (!-d $request_filename){
   set $rule_2 1$rule_2;
}
if (!-f $request_filename){
   set $rule_2 2$rule_2;
}
if ($rule_2 = "21"){
   rewrite ^/(.*)$ /viewpost.php?id=$1 last;
}
```

You should configure a HTTPS access. Maybe with Lets'encrypt (french tuto: https://www.citizenz.info/let-s-encrypt-et-nginx-config-rapide-sous-ubuntu)

### MySQL settings
You need a MySQL database. Import ft4a.sql file in phpMyAdmin or in command line.
Please add your settings in xbt_config table :
- redirect_url
- pid_file
- torrent_pass_private_key
- listen_port

MySQL connection settings are in /web/includes/sql.php

You need to change a few info in xbt_config table:
```
redirect_url, http://www.example.com <--- your real website URL
pid_file, /var/run/xbt_tracker_example.pid <--- give a name
torrent_pass_private_key, MyPrivateKeyWithLettersAndNumbers <--- put a random key here (25 caracters at least)
listen_port, xxxxx <--- put the tracker port (don't forget to configure firewall!)
```

### Crontab
To delete non validated user accounts older than 7 days, create the following crontab:
```
@daily php /var/www/ft4a.fr/private/users-prune.php
```
You can change to whatever you want: 15 days, 2 months, ... Look at the /private/users-prune.php file.

### Google Re-Captcha
Get a Google Re-Captcha account.
Then you have to change Google Re-Captcha keys in :
- web/contact.php
- web/signup.php
- web/recup_pass.php

### Mail settings
You have to change mail login, pass and smtp server in web/includes/config.php:
```
define('SITEMAIL','contact@example.com');
define('SITEMAILPASSWORD','xxxxxxxxxxxxxxxxxxxx');
define('SMTPHOST','mail.example.com');
define('SMTPPORT','587');
```
Then you have to change mail info in :
- web/contact.php
- web/signup.php
- web/recup_pass.php
