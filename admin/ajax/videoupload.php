<?php
/**  
 * Video Image, Video, Subtitle srt file uploads.
 *
 * @category   Apptha
 * @package    Contus video Gallery
 * @version    2.7.1
 * @author     Apptha Team <developers@contus.in>
 * @author     Martin Zaloudek, www.zal.cz, 2016 (as contributor)
 * @copyright  Copyright (C) 2014 Apptha. All rights reserved.
 * @license    GNU General Public License http://www.gnu.org/copyleft/gpl.html 
 */

class VgAjaxVideoUpload {

    /**
     * Starts upload process. Called on WP AJAX action=uploadvideo
     * 
     * @param array $file - commonly = $_FILES['myfile'] 
     */
    public static function main ($file) {
        
        $file_name = '';
       	$errorcode = 0;
        
       	try {
            if (isset( $_GET['error'] ) &&  $_GET['error'] == 'cancel') {
                throw new Exception( '', 1 );
            }
        
            if ( isset( $_GET['processing'] ) ) {
                $pro = $_GET['processing'];
            }else{
                $pro = 1;
            }
        
            switch (@$_POST['mode']) {
                case 'video':
                    $allowedExtensions = array( 'flv', 'mp4', 'm4v', 'm4a', 'mov', 'mp4v', 'f4v', 'mp3');
                    break;
                case 'image':
                    $allowedExtensions = array( 'jpg', 'jpeg', 'png' );
                    break;
                case 'srt':
                    $allowedExtensions = array( 'srt' );
                    break;
                default:
                    throw new Exception( '', 14 );
                    break;
            }
        
            if ( ( $pro == 1 ) && ( empty( $file ) ) ) {
                throw new Exception( '',  13 );
            }
        
            self::check_file_upload_error( $file['error'] );
        
            if ( !self::is_allowed_extension( $file, $allowedExtensions ) ) {
                throw new Exception( '',  2 );
            }
            if ( self::isFilesizeExceeded() ) {
                throw new Exception( '',  3 );
            }
            
            $file_name = self::doupload( $file );
        } catch (Exception $_errorcode) {
            $errorcode = $_errorcode->getCode();
        }
        
        $errormsg[0]  = '<b>Upload Success:</b> File Uploaded Successfully';
        $errormsg[1]  = '<b>Upload Cancelled:</b> Cancelled by user';
        $errormsg[2]  = '<b>Upload Failed:</b> Invalid File type specified';
        $errormsg[3]  = '<b>Upload Failed:</b> Your File Exceeds Server Limit size';
        $errormsg[4]  = '<b>Upload Failed:</b> Unknown Error Occured';
        $errormsg[5]  = '<b>Upload Failed:</b> The uploaded file exceeds the upload_max_filesize directive in php.ini';
        $errormsg[6]  = '<b>Upload Failed:</b> The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        $errormsg[7]  = '<b>Upload Failed:</b> The uploaded file was only partially uploaded';
        $errormsg[8]  = '<b>Upload Failed:</b> No file was uploaded';
        $errormsg[9]  = '<b>Upload Failed:</b> Missing a temporary folder';
        $errormsg[10] = '<b>Upload Failed:</b> Failed to write file to disk';
        $errormsg[11] = '<b>Upload Failed:</b> File upload stopped by extension';
        $errormsg[12] = '<b>Upload Failed:</b> Unknown upload error.';
        $errormsg[13] = '<b>Upload Failed:</b> Please check post_max_size in php.ini settings';
        $errormsg[14] = '<b>Upload Failed:</b> Missing or wrong parameter "mode".';
        
        echo '<script type="text/javascript">';
        echo    'window.top.window.updateQueue('. balanceTags( $errorcode ) . ', "' . balanceTags( $errormsg[$errorcode] ) . '", "' . balanceTags( $file_name ) . '");';
        echo '</script>';
    }
    
    
    /**
     * Map error getted from $_FILES[...]['error'] to own message errors.
     * 
     * @param int $file_error
     * @return bool
     * @throws Exception
     */
    public static function check_file_upload_error( $file_error ) {
        switch ( $file_error ) {
            case 1: throw new Exception( '', 5 );
            case 2: throw new Exception( '', 6 );
            case 3: throw new Exception( '', 7 );
            case 4: throw new Exception( '', 8 );
            case 6: throw new Exception( '', 9 );
            case 7: throw new Exception( '', 10 );
            case 8: throw new Exception( '', 11 );
            case 0: return true;
            default: throw new Exception( '', 12 );
        }
    }
    
    /**
     * Check the  upload file extension for  allowed format or Not
     * @param array $file {
     *     @option string name
     * }
     * @param string[] $allowedExtensions
     * @return boolean
     */
    public static function is_allowed_extension( $file, $allowedExtensions ) {
        $filename = $file['name'];
        $extension = explode('.', $filename );
        $extension = end($extension);
        $output   = in_array($extension, $allowedExtensions );
        if ( ! $output ) {
            return false;
        } else {
            return true;
        }
    }
    
    
    /**
     * Check if file upload size exceeds max post size
     * @return boolean
     */
    public static function isFilesizeExceeded() {
        $POST_MAX_SIZE = ini_get( 'post_max_size' );
        $post_max_size = substr( $POST_MAX_SIZE, -1 );
        $post_max_size_value = ( $post_max_size == 'M' ? 1048576 : ( $post_max_size == 'K' ? 1024 : ( $post_max_size == 'G' ? 1073741824 : 1 ) ) );
        if ( $_SERVER['CONTENT_LENGTH'] > $post_max_size_value * ( int ) $POST_MAX_SIZE && $POST_MAX_SIZE ) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Move the  file  to video gallery folder ( OR ) Amazon  s3 bucket.
     * @param array $file {
     *     @option string name
     *     @option string tmp_name
     * }
     * @return string - final filename
     * @throws Exception
     */
    public static function doupload( $file ) {
    
        global $wpdb;
        $wp_upload_dir = wp_upload_dir();
        $upload_url    = $wp_upload_dir['baseurl'];
        $site_url      = get_option('siteurl'); 
        $uPath         = str_replace($site_url,'',$upload_url);
        $uPath         = $uPath.'/videogallery';
        if ( $uPath != '' ) {
            $dir = ABSPATH . trim( $uPath ) . '/';
            $url = trailingslashit( get_option( 'siteurl' ) ) . trim( $uPath ) . '/';
            if ( ! wp_mkdir_p( $dir ) ) {
                $message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?', 'hdflv' ), $dir );
                $uploads['error'] = $message;
                return $uploads;
            }
            $uploads    = array( 'path' => $dir, 'url' => $url, 'error' => false );
            $uploadpath = $uploads['path'];
        } else {
            $uploadpath = ABSPATH;
        }
    
        $destination_path = $uploadpath;
    
        /** @var string $file_name */
        $file_name = self::generateDestinationFilename($file['name']);
    
        $pathinfo = pathinfo($file_name);
        $extension = strtolower($pathinfo['extension']);	
    
        // File move to destination directory
        $amazon_s3_bucket_setting = $wpdb->get_var("SELECT player_colors FROM ".$wpdb->prefix."hdflvvideoshare_settings");
        $player_colors = unserialize($amazon_s3_bucket_setting);
        if($extension !== 'srt' && $player_colors['amazonbuckets_enable'] && $player_colors['amazonbuckets_name'] ){
            $s3bucket_name  =  $player_colors['amazonbuckets_name'];
            include_once(APPTHA_VGALLERY_BASEDIR . '/helper/s3_config.php');
            if($s3->putObjectFile($file['tmp_name'],$s3bucket_name,$file_name,S3::ACL_PUBLIC_READ)){
                $file_name = 'http://'.$s3bucket_name.'.s3.amazonaws.com/'.$file_name;
            }else{
                throw new Exception( '', 4 );
            }
            // End Amazon S3 bucket  storage data
        } else {	  
            $target_path = $destination_path . '' . $file_name;
            if( !move_uploaded_file( $file['tmp_name'], $target_path ) ) {
                throw new Exception( '', 4 );
            }
        }
        sleep( 1 );
        return $file_name;
    }
    
    
    /**
     * Based on uploaded file generate a new unique filename, how to store it in videogallery upload directory.
     * Note: Helper function for `doupload()`.
     * 
     * @param string $originalFilename
     * @return string - Filename, how to store in videogallery upload directory
     */
    public static function generateDestinationFilename( $originalFilename ) { 
        global $wpdb;
        $last_vid = $wpdb->get_var('select MAX( vid ) from ' . $wpdb->prefix . 'hdflvvideoshare');
        $next_vid = $last_vid + 1;
        $pathinfo = pathinfo($originalFilename);
        $extension = strtolower($pathinfo['extension']);	
    
        if($extension === 'jpeg'){
            $extension = 'jpg';
        }
        
        // For better orientation in filesystem add part of original filename
        $normalizedFilename = preg_replace('/[\-]+$/u', '',       // 5) Remove '-' from string ending (if exists)
            preg_replace('/^[\-]+/u', '',                         // 4) Remove '-' from string beginning (if exists)
                substr(                                           // 3) Limit filename size to 32 chars
                    preg_replace('/[^A-Za-z0-9()\[\]]+/u', '-',   // 2) Replace special chars to '-'
                        remove_accents(                           // 1) Remove diacritics by global WP function
                            $pathinfo['filename']
                        )
                    ), 0, 32
                )
            )
        );
        
        switch($extension) {
            case 'srt':
                return 'video-' . rand(1000,9099) . (!empty($normalizedFilename) ? '-' . $normalizedFilename : '') . '.' . $extension;
            case 'jpg':
            case 'png':
            case 'gif':
                return $next_vid . '-thumb-' . rand(1000,9099) . (!empty($normalizedFilename) ? '-' . $normalizedFilename : '') . '.' . $extension;
            default:
                return $next_vid . '-video-' . rand(1000,9099) . (!empty($normalizedFilename) ? '-' . $normalizedFilename : '') . '.' . $extension;	
        }
    }
}