<?php

/*
Copyright 2008-2015 iThemes (email: support@ithemes.com)

Written by Chris Jean
Version 1.4.15

Version History
	See history.txt file
*/


if ( !class_exists( 'iThemesFileUtility' ) ) {
	class iThemesFileUtility {
		// For backwards compatibility
		public static function uploadFile( $file_id ) {
			return iThemesFileUtility::upload_file( $file_id );
		}
		
		public static function upload_file( $file_id ) {
			$overrides = array( 'test_form' => false );
			$file = wp_handle_upload( $_FILES[$file_id], $overrides );
			
			if ( isset( $file['error'] ) )
				return new WP_Error( 'upload_error', $file['error'] );
			
			$url = $file['url'];
			$type = $file['type'];
			$file = $file['file'];
			$title = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
			$content = '';
			
			if ( $image_meta = @wp_read_image_metadata( $file ) ) {
				if ( trim( $image_meta['title'] ) )
					$title = $image_meta['title'];
				if ( trim( $image_meta['caption'] ) )
					$content = $image_meta['caption'];
			}
			
			$attachment = array(
				'post_mime_type'	=> $type,
				'guid'				=> $url,
				'post_title'		=> $title,
				'post_content'		=> $content
			);
			
			$id = wp_insert_attachment( $attachment, $file );
			if ( !is_wp_error( $id ) )
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
			
			
			$data = array(
				'id'		=> $id,
				'url'		=> $url,
				'type'		=> $type,
				'file'		=> $file,
				'title'		=> $title,
				'caption'	=> $content
			);
			
			return $data;
		}
		
		// For backwards compatibility. Note how the default crop changed from false to true in the new version
		public static function resizeImage( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
			return iThemesFileUtility::resize_image( $file, $max_w, $max_h, $crop, $suffix, $dest_path, $jpeg_quality );
		}
		
		public static function resize_image( $file, $max_w = 0, $max_h = 0, $crop = true, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
			if ( is_numeric( $file ) ) {
				$file_info = iThemesFileUtility::get_file_attachment( $file );
				
				if ( false === $file_info )
					return new WP_Error( 'error_loading_image_attachment', "Could not find requested file attachment ($file)" );
				
				$file = $file_info['file'];
			}
			
			if ( empty( $file ) )
				return new WP_Error( 'error_resizing_image_empty_argument', 'An empty $file argument was passed' );
			if ( ! file_exists( $file ) )
				return new WP_Error( 'error_resizing_image', 'Unable to find requested file' );
			
			if ( preg_match( '/\.ico$/', $file ) )
				return array( 'file' => $file, 'url' => iThemesFileUtility::get_url_from_file( $file ), 'name' => basename( $file ) );
			
			list( $orig_w, $orig_h, $orig_type ) = getimagesize( $file );
			$dims = iThemesFileUtility::_image_resize_dimensions( $orig_w, $orig_h, $max_w, $max_h, $crop );
			if ( ! $dims || is_wp_error( $dims ) )
				return new WP_Error( 'error_resizing_image', 'Could not resize image' );
			list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;
			
			if ( ( $orig_w == $dst_w ) && ( $orig_h == $dst_h ) )
				return array( 'file' => $file, 'url' => iThemesFileUtility::get_url_from_file( $file ), 'name' => basename( $file ) );
			
			if ( ! $suffix )
				$suffix = "resized-image-${dst_w}x${dst_h}";
			
			$info = pathinfo( $file );
			$dir = $info['dirname'];
			$ext = $info['extension'];
			$name = basename( $file, ".${ext}" );
			
			$destination_ext = ( preg_match( '/^jpe?g$/i', $ext ) ) ? 'jpg' : $ext;
			
			if ( ! is_null( $dest_path ) && $_dest_path = realpath( $dest_path ) )
				$dir = $_dest_path;
			$destfilename = "${dir}/${name}-${suffix}.${ext}";
				
			if ( file_exists( $destfilename ) ) {
				if ( filemtime( $file ) > filemtime( $destfilename ) )
					unlink( $destfilename );
				else
					return array( 'file' => $destfilename, 'url' => iThemesFileUtility::get_url_from_file( $destfilename ) );
			}
			
			if ( version_compare( $GLOBALS['wp_version'], '3.4.9', '>' ) ) {
				// Compat code taken from pre-release 3.5.0 code.
				
				if ( ! file_exists( $file ) )
					return new WP_Error( 'error_loading_image', sprintf( __( 'File &#8220;%s&#8221; doesn&#8217;t exist?' ), $file ) );
				
				if ( ! function_exists('imagecreatefromstring') )
					return new WP_Error( 'error_loading_image', __( 'The GD image library is not installed.' ) );
				
				// Set artificially high because GD uses uncompressed images in memory
				@ini_set( 'memory_limit', apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );
				$image = imagecreatefromstring( file_get_contents( $file ) );
				
				if ( ! is_resource( $image ) )
					return new WP_Error( 'error_loading_image', sprintf( __( 'File &#8220;%s&#8221; is not an image.' ), $file ) ); 
			} 
			else { 
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				
				$image = wp_load_image( $file );
				if ( ! is_resource( $image ) )
					return new WP_Error( 'error_loading_image', $image );	
			}
			
			
			// ImageMagick cannot resize animated PNG files yet, so this only works for
			// animated GIF files.
			$animated = false;
			if ( iThemesFileUtility::is_animated_gif( $file ) ) {
				$coalescefilename = "${dir}/${name}-coalesced-file.${ext}";
				
				$safe_file_name = escapeshellarg( $file );
				$safe_coalescefilename = escapeshellarg( $coalescefilename );
				
				if ( ! file_exists( $coalescefilename ) ) {
					system( "convert $safe_file_name -coalesce $safe_coalescefilename" );
				}
				
				if ( file_exists( $coalescefilename ) ) {
					$safe_crop_argument = escapeshellarg( "{$src_w}x{$src_h}+{$src_x}+{$src_y}!" );
					$safe_destfilename = escapeshellarg( $destfilename );
					
					system( "convert -crop $safe_crop_argument $safe_coalescefilename $safe_destfilename" );
					
					if ( file_exists( $destfilename ) ) {
						$safe_resize_argument = escapeshellarg( "${dst_w}x${dst_h}" );
						
						system( "mogrify -resize $safe_resize_argument $safe_destfilename" );
						system( "convert -layers optimize $safe_destfilename" );
						
						$animated = true;
					}
				}
			}
			
			
			if ( ! $animated ) {
				$newimage = imagecreatetruecolor( $dst_w, $dst_h );
				
				// preserve PNG transparency
				if ( IMAGETYPE_PNG == $orig_type && function_exists( 'imagealphablending' ) && function_exists( 'imagesavealpha' ) ) {
					imagealphablending( $newimage, false );
					imagesavealpha( $newimage, true );
				}
				
				imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );
				
				// we don't need the original in memory anymore
				if ( $orig_type == IMAGETYPE_GIF ) {
					if ( ! imagegif( $newimage, $destfilename ) )
						return new WP_Error( 'resize_path_invalid', __( 'Resize path invalid' ) );
				}
				elseif ( $orig_type == IMAGETYPE_PNG ) {
					if ( ! imagepng( $newimage, $destfilename ) )
						return new WP_Error( 'resize_path_invalid', __( 'Resize path invalid' ) );
				}
				else {
					// all other formats are converted to jpg
					$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
					if ( ! imagejpeg( $newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality ) ) )
						return new WP_Error( 'resize_path_invalid', __( 'Resize path invalid' ) );
				}
				
				imagedestroy( $newimage );
			}
			
			imagedestroy( $image );
			
			
			// Set correct file permissions
			$stat = stat( dirname( $destfilename ) );
			$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
			@ chmod( $destfilename, $perms );
			
			
			return array( 'file' => $destfilename, 'url' => iThemesFileUtility::get_url_from_file( $destfilename ), 'name' => basename( $destfilename ) );
		}
		
		// Customized image_resize_dimensions() from 2.6.3 wp-admin/includes/media.php (cheanged to resize to fill on crop)
		public static function _image_resize_dimensions( $orig_w, $orig_h, $dest_w = 0, $dest_h = 0, $crop = false ) {
			if ( ( $orig_w <= 0 ) || ( $orig_h <= 0 ) )
				return new WP_Error ( 'error_resizing_image', "Supplied invalid original dimensions ($orig_w, $orig_h)" );
			if ( ( $dest_w < 0 ) || ( $dest_h < 0 ) )
				return new WP_Error ( 'error_resizing_image', "Supplied invalid destination dimentions ($dest_w, $dest_h)" );
			
			
			if ( ( $dest_w == 0 ) || ( $dest_h == 0 ) )
				$crop = false;
			
			
			$new_w = $dest_w;
			$new_h = $dest_h;
			
			$s_x = 0;
			$s_y = 0;
			
			$crop_w = $orig_w;
			$crop_h = $orig_h;
			
			
			if ( $crop ) {
				$cur_ratio = $orig_w / $orig_h;
				$new_ratio = $dest_w / $dest_h;
				
				if ( $cur_ratio > $new_ratio ) {
					$crop_w = floor( $orig_w / ( ( $dest_h / $orig_h ) / ( $dest_w / $orig_w ) ) );
					$s_x = floor( ( $orig_w - $crop_w ) / 2 );
				}
				elseif ( $new_ratio > $cur_ratio ) {
					$crop_h = floor( $orig_h / ( ( $dest_w / $orig_w ) / ( $dest_h / $orig_h ) ) );
					$s_y = floor( ( $orig_h - $crop_h ) / 2 );
				}
			}
			else
				list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
			
			
			$s_x = intval( $s_x );
			$s_y = intval( $s_y );
			$new_w = intval( $new_w );
			$new_h = intval( $new_h );
			$crop_w = intval( $crop_w );
			$crop_h = intval( $crop_h );
			
			return array( 0, 0, $s_x, $s_y, $new_w, $new_h, $crop_w, $crop_h );
		}
		
		public static function getURLFromFile( $file ) {
			return iThemesFileUtility::get_url_from_file( $file );
		}
		
		public static function get_url_from_file( $file ) {
			return get_option( 'siteurl' ) . str_replace( '\\', '/', str_replace( rtrim( ABSPATH, '\\\/' ), '', $file ) );
		}
		
		public static function getFileFromURL( $url ) {
			return iThemesFileUtility::get_file_from_url( $url );
		}
		
		public static function get_file_from_url( $url ) {
			if ( ! defined( 'WPMU_PLUGIN_URL' ) || ! preg_match( '|//[^/]+/files/|', $url ) )
				return ABSPATH . str_replace( get_option( 'siteurl' ), '', $url );
			
			return ABSPATH . get_option( 'upload_path' ) . '/' . ltrim( $url, get_option( 'siteurl' ) . '/files' );
		}
		
		public static function get_mime_type( $file ) {
			if ( preg_match( '|^https?://|', $file ) )
				$file = get_file_from_url( $file );
			
			return mime_content_type( $file );
		}
		
		public static function getFileAttachment( $id ) {
			return iThemesFileUtility::get_file_attachment( $id );
		}
		
		public static function get_file_attachment( $id ) {
			if ( wp_attachment_is_image( $id ) ) {
				$post = get_post( $id );
				
				$file = array();
				$file['ID'] = $id;
				$file['file'] = get_attached_file( $id );
				$file['url'] = wp_get_attachment_url( $id );
				$file['title'] = $post->post_title;
				$file['name'] = basename( get_attached_file( $id ) );
				
				return $file;
			}
			
			return false;
		}
		
		public static function deleteFileAttachment( $id ) {
			return iThemesFileUtility::delete_file_attachment( $id );
		}
		
		public static function delete_file_attachment( $id ) {
			if ( wp_attachment_is_image( $id ) ) {
				$file = get_attached_file( $id );
				
				$info = pathinfo( $file );
				$ext = $info['extension'];
				$name = basename( $file, ".$ext" );
				
				
				if ( $dir = opendir( dirname( $file ) ) ) {
					while ( false !== ( $filename = readdir( $dir ) ) ) {
						if ( preg_match( "/^$name-resized-image-\d+x\d+\.$ext$/", $filename ) )
							unlink( dirname( $file ) . '/' . $filename );
						elseif ( "$name-coalesced-file.$ext" === $filename )
							unlink( dirname( $file ) . '/' . $filename );
					}
					
					closedir( $dir );
				}
				
				unlink( $file );
				
				
				return true;
			}
			
			return false;
		}
		
		// Can only detect animated GIF files, which is fine because ImageMagick doesn't seem
		// to be able to resize animated PNG (MNG) files yet.
		public static function is_animated_gif( $file ) {
			$filecontents=file_get_contents($file);
			
			$str_loc=0;
			$count=0;
			while ($count < 2) # There is no point in continuing after we find a 2nd frame
			{
				$where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);
				if ($where1 === FALSE)
				{
					break;
				}
				else
				{
					$str_loc=$where1+1;
					$where2=strpos($filecontents,"\x00\x2C",$str_loc);
					if ($where2 === FALSE)
					{
						break;
					}
					else
					{
						if ($where1+8 == $where2)
						{
							$count++;
						}
						$str_loc=$where2+1;
					}
				}
			}
			
			if ($count > 1)
				return(true);
			return(false);
		}
	}
}

if( ! function_exists( 'mime_content_type' ) ) {
	function mime_content_type( $filename ) {
		$mime_types = array(
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);
		
		$ext = strtolower( array_pop( explode( '.', $filename ) ) );
		
		if ( array_key_exists( $ext, $mime_types ) )
			return $mime_types[$ext];
		elseif ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME );
			$mimetype = finfo_file( $finfo, $filename );
			finfo_close( $finfo );
			return $mimetype;
		}
		else
			return 'application/octet-stream';
	}
}

?>
