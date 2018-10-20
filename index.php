<?php
require_once( 'functions.php' );

$uri = $_SERVER['REQUEST_URI'];
$append_url = preg_replace( '|^' . preg_quote( $rel_this ) . '|ims', '', $uri );
$url = $orig_home . $append_url;

$avoid_cache = false;
if ( isset( $avoidqs ) && $avoidqs && array_key_exists( $avoidqs, $_GET ) ) :
    $avoid_cache = true;
    $url = preg_replace( '|\?(.*)\&' . preg_quote( $avoidqs ) . '$|', '?$1', $url );
    $url = preg_replace( '|\?' . preg_quote( $avoidqs ) . '$|', '', $url );
    if ( isset( $clearqs ) && ( $clearqs == $_GET[ $avoidqs ] ) ) :
        array_map( 'unlink', glob( get_cache_dir() . '*.txt' ) );
    endif;
endif;

$cont = url_get_contents( $url, array( array( 'option' => CURLOPT_USERPWD, 'value' => $username . ':' . $password ) ), $avoid_cache ? 0 : $timeout );
$req = $cont[ 'body' ];
$header = $cont[ 'header' ];
$httpcode = $cont[ 'httpcode' ];

$do_replace = false;

if ( array_key_exists( 'Content-Type', $header ) ) {
    foreach ( array( 'text/', 'javascript', 'html', 'xml' ) as $text_indicator ) {
        if ( strpos( trim( $header[ 'Content-Type' ] ), $text_indicator ) !== false ) {
            $do_replace = true;
            break;
        }
    }
}

foreach ( $regexps as $regexp ) :
    if ( $do_replace ) $req = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $req );
    foreach ( $header as $h_key => $h_val ) :
        $header[ $h_key ] = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $h_val );
    endforeach;
endforeach;

if ( $httpcode && ( 200 != $httpcode ) ) http_response_code( $httpcode );

foreach ( $header as $h_key => $h_val ) if ( in_array( $h_key, $copy_headers ) ) header( $h_key . ':' . $h_val );

echo $req;
