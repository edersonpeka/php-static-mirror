<?php

// static site relative path
$rel_this = '/';

// dynamic site URL
$orig_home = 'http://dominio.com/oculto/';

// basic http authentication
$username = 'usuario';
$password = 'senha';

$timeout = 42;

// querystring key for cache avoiding - please, change it!
$avoidqs = 'static-mirror-avoid-cache';

// querystring value for cache cleansing - please, change it!
$clearqs = 'dramatic';

// all possible rewrite rules (regular expressions)
$regexps = array(
    array(
        'from' => 'https?\:\/\/dominio\.com\/oculto',
        'to' => 'https://dominio.com',
    ),
    array(
        'from' => '"https?'. preg_quote( ':\/\/dominio.com\/oculto\/' ),
        'to' => '"https:\/\/dominio.com\/',
    ),
    array(
        'from' => 'href\s*=\s*"\/oculto\/',
        'to' => 'href="/',
    ),
    array(
        'from' => 'src\s*=\s*"\/oculto\/',
        'to' => 'src="/',
    ),
    array(
        'from' => 'action\s*=\s*"\/oculto\/',
        'to' => 'action="/',
    ),
);

// headers to be preserved from original (dynamic) to copy (static)
$copy_headers = array( 'Content-Type', 'Expires', 'Cache-Control', 'Pragma' );
