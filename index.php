<?php
require_once( 'functions.php' );

// relative URL (requested of the static version)
$uri = $_SERVER['REQUEST_URI'];
// extracts only the relevant part
$append_url = preg_replace( '|^' . preg_quote( $rel_this ) . '|ims', '', $uri );
// assembles the original URL (dynamic version)
$url = $orig_home . $append_url;

// should we ignore the cached version for this request?
$avoid_cache = false;
if ( isset( $avoidqs ) && $avoidqs && array_key_exists( $avoidqs, $_GET ) ) :
    $avoid_cache = true;
    $url = preg_replace( '|\?(.*)\&' . preg_quote( $avoidqs ) . '$|', '?$1', $url );
    $url = preg_replace( '|\?' . preg_quote( $avoidqs ) . '$|', '', $url );
    // should we not just ignore the cached version, but wipe it as well?
    if ( isset( $clearqs ) && ( $clearqs == $_GET[ $avoidqs ] ) ) :
        array_map( 'unlink', glob( get_cache_dir() . '*.txt' ) );
    endif;
endif;

// fetches the original URL or its cached copy (if not expired)
$cont = url_get_contents( $url, array( array( 'option' => CURLOPT_USERPWD, 'value' => $username . ':' . $password ) ), $avoid_cache ? 0 : $timeout );
// parts of the response
$req = $cont[ 'body' ];
$header = $cont[ 'header' ];
$httpcode = $cont[ 'httpcode' ];

// should we replace the URLs in the response?
// (is it a textual response, not binary? are we able to handle it?)
$do_replace = false;
if ( array_key_exists( 'Content-Type', $header ) ) {
    foreach ( array( 'text/', 'javascript', 'html', 'xml' ) as $text_indicator ) {
        if ( strpos( trim( $header[ 'Content-Type' ] ), $text_indicator ) !== false ) {
            $do_replace = true;
            break;
        }
    }
}

// for each replacing rule, apply it to the body of the response...
foreach ( $regexps as $regexp ) :
    if ( $do_replace ) $req = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $req );
    // ... and to the headers as well
    foreach ( $header as $h_key => $h_val ) :
        $header[ $h_key ] = preg_replace( '|' . $regexp[ 'from' ] . '|ims', $regexp[ 'to' ], $h_val );
    endforeach;
endforeach;

// response code
if ( $httpcode && ( 200 != $httpcode ) ) http_response_code( $httpcode );

// for each header, if it's supposed to be copied, copies it
foreach ( $header as $h_key => $h_val ) if ( in_array( $h_key, $copy_headers ) ) header( $h_key . ':' . $h_val );

// and echoes the maybe-replaced-response
echo $req;
