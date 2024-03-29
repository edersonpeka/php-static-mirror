<?php
if ( !file_exists( 'config.php' ) ) die( 'Configuration file not found.' );
require_once( 'config.php' );

// detects cache directory
function get_cache_dir() {
    $dir = rtrim( __DIR__, '/' ) . '/cache/';
    if ( ( !is_dir( $dir ) || !is_writable( $dir ) ) && is_writable( __DIR__ ) )
        mkdir( $dir, 0664, true );
    if ( !is_dir( $dir ) || !is_writable( $dir ) )
        $dir = '/tmp/cachecurl/';
    if ( (!is_dir( $dir ) || !is_writable( $dir ) ) && is_writable( '/tmp/' ) )
        mkdir( $dir, 0664, true );
    return $dir;
}

// tries to invalidate cache before this moment
// if something goes wrong, tries to remove every cached file
function expire_cache() {
    $dir = get_cache_dir();
    $tokenfile = $dir . 'token.txt';
    $ret = touch( $tokenfile );
    if ( !$ret ) {
        $arr = array_unique( array_map( 'unlink', glob( get_cache_dir() . '*.txt' ) ) );
        if ( count( $arr ) == 1 ) $ret = $arr[0];
    }
    return $ret;
}

// fetches an URL content and keeps its copy
function url_get_contents( $url, $opts = array(), $exptime = 1, $curltimeout = 10 ) {
    $dir = get_cache_dir();
    $exptime *= 3600;
    $httpcode = 200;
    $md5 = md5( $url );
    $tokenfile = $dir . 'token.txt';
    $cachefile = $dir . $md5 . '.txt';
    $cachefileh = $dir . $md5 . '.header.txt';
    $dt_token = file_exists( $tokenfile ) ? date( 'U', filemtime( $tokenfile ) ) : 0;
    $dt_cached = file_exists( $cachefile ) ? date( 'U', filemtime( $cachefile ) ) : 0;
    $dt_limit = date( 'U' ) - $exptime;
    if ( file_exists( $cachefile ) && $exptime && ( $dt_cached > $dt_token ) && ( $dt_cached > $dt_limit ) ) {
        $ret = file_get_contents( $cachefile );
        $header = file_get_contents( $cachefileh );
    } else {
        if ( function_exists( 'curl_init' ) ) {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_HEADER, 1 );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $curltimeout );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
            if ( is_array( $opts ) && count( $opts ) ) {
                foreach ( $opts as $opt ) curl_setopt( $ch, $opt[ 'option' ], $opt[ 'value' ] );
            }
            $response = curl_exec( $ch );
            $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
            $header = substr( $response, 0, $header_size );
            $ret = substr( $response, $header_size );
            $httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
        } else {
            $context = null;
            if ( is_array( $opts ) && count( $opts ) ) {
                foreach ( $opts as $opt ) {
                    if ( CURLOPT_USERPWD == $opt[ 'option' ] ) {
                        $context = stream_context_create( array(
                            'http' => array(
                                'header' => 'Authorization: Basic ' . base64_encode( $opt[ 'value' ] ),
                            ),
                        ) );
                    }
                }
            }
            $ret = file_get_contents( $url, false, $context );
            $header = implode( PHP_EOL, $http_response_header );
            $httpcode = '';
            preg_match( '|^HTTP/[\S]+\s+([\d]+)\s+|ms', $header, $m );
            if ( count( $m ) > 1 ) $httpcode = $m[ 1 ];
        }
        if ( ( 500 != $httpcode ) && ( $ret !== false ) && ( '' != trim( $ret ) ) && is_dir( $dir ) && is_writeable( $dir ) ) {
            file_put_contents( $cachefile, $ret );
            if ( $header ) {
                file_put_contents( $cachefileh, $httpcode . PHP_EOL . $header );
            }
        }
    }
    $header = array_filter( explode( PHP_EOL, $header ) );
    $aux = array();
    foreach ( $header as $line ) {
        $parts = explode( ':', $line );
        if ( count( $parts ) > 1 ) {
            $aux[ array_shift( $parts ) ] = implode( ':', $parts );
        } elseif ( ( count( $parts ) == 1 ) && ( is_numeric( $parts[0] ) ) ) {
            $httpcode = $parts[0];
        }
    }
    $header = array_filter( $aux );
    return array( 'httpcode' => $httpcode, 'header' => $header, 'body' => $ret );
}

