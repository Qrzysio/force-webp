<?php
/*
Plugin Name: Force WebP
Plugin URI: https://wordpress.org/plugins/force-webp/
Description: this plugin forces every uploaded image to be converted to webp format, so you can forget about old formats like jpg and png
Version: 1.0
Author: Qrzysio
Author URI: https://github.com/Qrzysio
Update URI: https://raw.githubusercontent.com/Qrzysio/force-webp/refs/heads/master/updates.json
*/


if (!defined('ABSPATH')) {
    exit;
}

class WebP_Upload_Optimizer_MU {
    const QUALITY = 85;

    public static function init() {
        add_filter('wp_handle_upload_prefilter', [__CLASS__, 'prefilter_name'], 10, 1);
        add_filter('wp_handle_upload', [__CLASS__, 'convert_to_webp'], 10, 1);
        add_filter('mime_types', [__CLASS__, 'allow_webp']);
    }

    public static function prefilter_name($file) {
        $file['name'] = self::sanitize_file_name($file['name']);
        return $file;
    }

    public static function convert_to_webp($upload) {
        if (empty($upload['file']) || empty($upload['type'])) {
            return $upload;
        }

        $file_path = $upload['file'];
        $mime_type = $upload['type'];

        // obsługujemy tylko JPG/JPEG i PNG
        if (!in_array($mime_type, ['image/jpeg', 'image/png'], true)) {
            return $upload;
        }

        if (!extension_loaded('imagick')) {
            return $upload; // brak Imagick: pomijamy konwersję
        }

        try {
            $image = new Imagick($file_path);

            // jeśli PNG z przezroczystością, zachowujemy ją
            if ($mime_type === 'image/png') {
                $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                $image->setBackgroundColor(new ImagickPixel('transparent'));
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(self::QUALITY);
            $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);

            if ($image->writeImage($webp_path)) {
                // usuwamy oryginał
                @unlink($file_path);

                // aktualizujemy ścieżkę, URL i typ
                $upload['file'] = $webp_path;
                $upload['url']  = str_replace(basename($file_path), basename($webp_path), $upload['url']);
                $upload['type'] = 'image/webp';
            }
            $image->clear();
            $image->destroy();

        } catch (Exception $e) {
            error_log('WebP Upload Optimizer MU: błąd konwersji - ' . $e->getMessage());
            // w razie błędu pozostawiamy oryginał
        }

        return $upload;
    }

    public static function allow_webp($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    private static function sanitize_file_name($filename) {
        $filename = mb_strtolower($filename, 'UTF-8');
        // usuwamy polskie znaki
        $filename = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $filename);
        // zamieniamy wszystko poza a-z0-9 . _ - na myślnik
        $filename = preg_replace('/[^a-z0-9\._-]/', '-', $filename);
        // pojedyncze myślniki
        $filename = preg_replace('/-+/', '-', $filename);
        return $filename;
    }
}

WebP_Upload_Optimizer_MU::init();
