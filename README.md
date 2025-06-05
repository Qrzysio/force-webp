=== Force WebP ===
Contributors: Qrzysio
Tags: webp, images, optimization, converter, upload
Requires at least: 6.7
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
This plugin forces every uploaded image to be converted to WebP format, so you can forget about old formats like JPG and PNG. It also removes the original file and automatically sets the alt text based on the filename.

== Installation ==
1. Download the repository and copy the `force-webp` folder into `wp-content/plugins/`.
2. Activate the plugin in the WordPress dashboard (Plugins → Installed Plugins → Force WebP).
3. From now on, any JPG/PNG file added to the Media Library will be converted to WebP (quality 85%) and the original file will be removed.

== Frequently Asked Questions ==

= Does the plugin support GIFs? =
No, it only supports JPG/JPEG and PNG. GIFs and other formats will be left unchanged.

= What happens if the server does not have Imagick with WebP support? =
The plugin will detect that WebP is not available and will keep the uploaded file in its original format.

= How can I change the compression quality? =
The default quality is 85%. To modify it, change the value of `const QUALITY` in the PHP file.

== Changelog ==
= 1.0 =
* initial release: convert JPG/PNG to WebP (85% quality), remove original files, rename files, and add alt text automatically.

== Upgrade Notice ==
= 1.0 =
First public release.
