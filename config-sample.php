<?php

$rel_this = '/';
$orig_home = 'http://dominio.com/oculto/';
$username = 'usuario';
$password = 'senha';
$timeout = 42;
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
$copy_headers = array( 'Content-Type', 'Expires', 'Cache-Control', 'Pragma' );
