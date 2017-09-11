<?php
require_once( 'functions.php' );

$uri = $_SERVER['REQUEST_URI'];
$append_url = preg_replace( '|^' . preg_quote( $rel_this ) . '|ims', '', $uri );
$url = $orig_home . $append_url;

$cont = url_get_contents( $url, array( array( 'option' => CURLOPT_USERPWD, 'value' => $username . ':' . $password ) ), $timeout );
$req = $cont[ 'body' ];
$header = $cont[ 'header' ];

$do_replace = true;

if ( array_key_exists( 'Content-Type', $header ) ) {
    if ( strpos( 'text/', trim( $header[ 'Content-Type' ] ) ) === 0 ) $do_replace = true;
}

if ( $do_replace ) foreach ( $regexps as $regexp ) :
    $req = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $req );
endforeach;

foreach ( $header as $h_key => $h_val ) foreach ( $regexps as $regexp ) :
    $header[ $h_key ] = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $h_val );
endforeach;

foreach ( $header as $h_key => $h_val ) if ( in_array( $h_key, $copy_headers ) ) header( $h_key . ':' . $h_val );

echo $req;
