<?php
require_once( 'functions.php' );

$uri = $_SERVER['REQUEST_URI'];
$append_url = preg_replace( '|^' . preg_quote( $rel_this ) . '|ims', '', $uri );
$url = $orig_home . $append_url;

$avoid_cache = false;
$avoidqs = 'static-mirror-avoid-cache';
if ( array_key_exists( $avoidqs, $_GET ) ) :
    $avoid_cache = true;
    $url = preg_replace( '|\?(.*)\&' . preg_quote( $avoidqs ) . '$|', '?$1', $url );
    $url = preg_replace( '|\?' . preg_quote( $avoidqs ) . '$|', '', $url );
endif;

$cont = url_get_contents( $url, array( array( 'option' => CURLOPT_USERPWD, 'value' => $username . ':' . $password ) ), $avoid_cache ? 0 : $timeout );
$req = $cont[ 'body' ];
$header = $cont[ 'header' ];

$do_replace = false;

if ( array_key_exists( 'Content-Type', $header ) ) {
    foreach ( array( 'text/', 'javascript', 'html', 'xml' ) as $text_indicator ) {
        if ( strpos( trim( $header[ 'Content-Type' ] ), $text_indicator ) !== false ) {
            $do_replace = true;
            break;
        }
    }
}

if ( $do_replace ) foreach ( $regexps as $regexp ) :
    $req = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $req );
endforeach;

foreach ( $header as $h_key => $h_val ) foreach ( $regexps as $regexp ) :
    $header[ $h_key ] = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $h_val );
endforeach;

foreach ( $header as $h_key => $h_val ) if ( in_array( $h_key, $copy_headers ) ) header( $h_key . ':' . $h_val );

echo $req;
