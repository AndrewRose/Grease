Grease
======

PHP editor written in PHP using wxPHP .. still early days.  Code needs cleaning up and refactoring as it's feature list has grown far greater than I anticipated.

![Screenshot](http://wxphp.org/images/static/application-grease-grease-3-200x150-ar.png)

To get up and running under Archlinux, download https://aur.archlinux.org/packages/php-wxwidgets-git/ and build with makepkg.  Setup php.ini to load wxwidgets.so module and run `php grease.wxphp` from the commandline.

To use search hit CTRL+F or click icon.  Enter to cycle search. Escape to close dialog.

To use search / replace click icon.  Enter search string in top input, replace string in bottom.  Hit Enter to cycle search and F4 to replace highlighted text.  Escape to close.

Debugging functionality is currently Alpha at very best.  To use be sure to start xdebugd.php:

./xdebugd.php &

php -d xdebug.remote_hostphp -d xdebug.remote_host=127.0.0.1 -d xdebug.remote_port=9000 -d xdebug.remote_autostart=1 -d xdebug.idekey=testkey test.php


If you need help I'm currently lurking in: irc.freenode.net #wxphp

Copyright (c) 2014 - Andrew Rose (hello@andrewrose.co.uk)
