<?php
require_once( 'config.php' );

function url_get_contents( $url, $opts = array(), $exptime = 1, $curltimeout = 10 ) {
    $exptime *= 3600;
    $dir = './cache/';
    if ( ( !is_dir( $dir) || !is_writable( $dir ) ) && is_writable( '.' ) )
        mkdir( $dir, 0664, true );
    if ( !is_dir( $dir ) || !is_writable( $dir ) )
        $dir = '/tmp/cachecurl/';
    if ( (!is_dir( $dir ) || !is_writable( $dir ) ) && is_writable( '/tmp/' ) )
        mkdir( $dir, 0664, true );
    $md5 = md5( $url );
    $cachefile = $dir . $md5 . '.txt';
    $cachefileh = $dir . $md5 . '.header.txt';
    if ( file_exists( $cachefile ) && ( !$exptime || date( 'U', filemtime( $cachefile ) ) > ( date('U') - $exptime ) ) ) {
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
            curl_close( $ch );
        } else {
            $ret = file_get_contents( $url );
            $header = ''; // TODO: get header without curl
        }
        if ( $ret !== false && '' != trim( $ret ) && is_dir( $dir ) ) {
            file_put_contents( $cachefile, $ret );
            if ( $header ) file_put_contents( $cachefileh, $header );
        }
    }
    $header = array_filter( explode( PHP_EOL, $header ) );
    $aux = array();
    foreach ( $header as $line ) {
        $parts = explode( ':', $line );
        if ( $parts ) $aux[ array_shift( $parts ) ] = implode( ':', $parts );
    }
    $header = array_filter( $aux );
    return array( 'header' => $header, 'body' => $ret );
}
