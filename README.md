Grease (BETA)
======

PHP editor written in PHP with remote debugger and plugin API.

![Screenshot](http://wxphp.org/images/static/application-grease-archlinux5.png)

To get up and running under Archlinux, install [yaourt](http://wiki.archlinux.org/index.php/yaourt) and then install the [wxPHP](http://wxphp.org/) environment and Grease with ```yaourt -S grease```

The debugging proxy xdebugd.php is started automatically by Grease.  Configure your PHP xdebug extension to, for example:

```
xdebug.remote_enable=on
xdebug.remote_host=127.0.0.1
xdebug.remote_port=9000
xdebug.remote_handler=dbgp
```

To debug a command line script:
```
php -d xdebug.remote_hostphp -d xdebug.remote_host=127.0.0.1 -d xdebug.remote_port=9000 -d xdebug.remote_autostart=1 -d xdebug.idekey=testkey test.php
```

If you wish to debug a remote server, run xdebugd.php on said server and configure the servers xdebug ini respectivly and finally configure your ~/.grease configuration to point to the server. 

To use search hit CTRL+F or click icon.  Enter to cycle search. Escape to close dialog.

To use search / replace click icon.  Enter search string in top input, replace string in bottom.  Hit Enter to cycle search and F4 to replace highlighted text.  Escape to close.

If you need help I'm currently lurking in: irc.freenode.net #wxphp

Copyright (c) 2014 - Andrew Rose (hello@andrewrose.co.uk)
