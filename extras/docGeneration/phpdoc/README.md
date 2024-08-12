# CP's Usage of PHPDoc

## Usage

1. Set your environment to use phpdoc and the php binary that it needs

In your .bashrc update your PATH.

        PATH=/nfs/local/linux/phpDocumentor/current/bin:$PATH

To verify,

        source ~/.bashrc
        php --version && phpdoc.php --version

should spit out something like:

        PHP 5.4.6 (cli) (built: Sep 11 2012 12:24:09) 
Copyright (c) 1997-2012 The PHP Group
Zend Engine v2.4.0, Copyright (c) 1998-2012 Zend Technologies
phpDocumentor version 2.0.0a10

2. Running

Head into your cp source's phpdoc dir and run:

        cd ..into this dir..
        phpdoc.php -c phpdoc.dist.xml

3. Viewing

That'll have created an output dir. There's an index.html file in there.

        cd output
        cat index.html

＼(＾O＾)／＼(＾O＾)／＼(＾O＾)／
