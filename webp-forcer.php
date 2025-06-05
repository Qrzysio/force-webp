<?php
/*
Plugin Name: Force WebP
Plugin URI: https://wordpress.org/plugins/force-webp/
Description: this plugin forces every uploaded image to be converted to webp format, so you can forget about old formats like jpg and png
Version: 1.0
Author: Qrzysio
Author URI: https://github.com/Qrzysio
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 8.3
Requires at least: 6.7
Tested up to: 6.8
*/

if (!defined('ABSPATH')) {
    exit;
}

class WebP_Upload_Optimizer {
    const QUALITY = 85;

    public static function init() {
        add_filter('wp_handle_upload_prefilter', [__CLASS__, 'prefilter_name']);
        add_filter('wp_handle_upload', [__CLASS__, 'convert_to_webp']);
        add_filter('mime_types', [__CLASS__, 'allow_webp']);
        add_action('add_attachment', [__CLASS__, 'set_alt_text']);
    }

    public static function prefilter_name( $file ) {
        $file['name'] = self::sanitize_file_name( $file['name'] );
        return $file;
    }

    public static function convert_to_webp( $upload ) {
        if ( empty( $upload['file'] ) || empty( $upload['type'] ) ) {
            return $upload;
        }

        $file_path = $upload['file'];
        $mime_type = $upload['type'];

        // JPEG and PNG only
        if ( ! in_array( $mime_type, ['image/jpeg','image/png'], true ) ) {
            return $upload;
        }

        if ( ! extension_loaded('imagick') ) {
            return $upload;
        }

        try {
            $image = new Imagick( $file_path );

            if ( $mime_type === 'image/png' ) {
                $image->setImageAlphaChannel( Imagick::ALPHACHANNEL_ACTIVATE );
                $image->setBackgroundColor( new ImagickPixel('transparent') );
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality( self::QUALITY );
            $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);

            if ( $image->writeImage( $webp_path ) ) {
                wp_delete_file( $file_path );
                $upload['file'] = $webp_path;
                $upload['url']  = str_replace( basename($file_path), basename($webp_path), $upload['url'] );
                $upload['type'] = 'image/webp';
            }

            $image->clear();
            $image->destroy();

        } catch ( Exception $e ) {
            // if an error, leave the original file
        }

        return $upload;
    }

    public static function allow_webp( $mimes ) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    public static function set_alt_text( $attachment_id ) {
        $mime = get_post_mime_type( $attachment_id );
        if ( strpos( $mime, 'image/' ) !== 0 ) {
            return;
        }
        $file_path = get_attached_file( $attachment_id );
        if ( ! $file_path ) {
            return;
        }
        $filename = pathinfo( $file_path, PATHINFO_FILENAME );
        $alt = str_replace('-', ' ', $filename );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
    }

    private static function sanitize_file_name( $filename ) {
        $filename = mb_strtolower( $filename, 'UTF-8' );
        $filename = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $filename);
        $filename = preg_replace('/[^a-z0-9\._-]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        return $filename;
    }
}

WebP_Upload_Optimizer::init();
