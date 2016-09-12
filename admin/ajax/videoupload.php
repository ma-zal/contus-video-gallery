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

require_once __DIR__ . '/../models/videosetting.php';  // Class SettingsModel

class VgAjaxVideoUpload {

    /**
     * Starts upload process. Called on WP AJAX action=uploadvideo
     * 
     * @param array $file - commonly = $_FILES['myfile'] 
     */
    public static function main ($file) {
        
        $file_name = '';
        
       	try {
            if (isset( $_GET['error'] ) &&  $_GET['error'] == 'cancel') {
                throw new Exception( 'Cancelled by user', 1 );
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
                    throw new Exception( 'Missing or wrong parameter `mode`', 14 );
                    break;
            }
        
            if ( ( $pro == 1 ) && ( empty( $file ) ) ) {
                throw new Exception( 'Please check post_max_size in php.ini settings',  13 );
            }
        
            self::check_file_upload_error( $file['error'] );
        
            if ( !self::is_allowed_extension( $file, $allowedExtensions ) ) {
                throw new Exception( 'Invalid File type specified',  2 );
            }
            if ( self::isFilesizeExceeded() ) {
                throw new Exception( 'Your File Exceeds Server Limit size',  3 );
            }
            
            $file_name = self::doupload( $file );
        
        echo '<script type="text/javascript">';
            echo    'window.top.window.updateQueue(0, "Upload Success", ' . json_encode( $file_name ) . ');';
        echo '</script>';
            
        } catch (Exception $error) {

            echo '<script type="text/javascript">';
            echo    'window.top.window.updateQueue('. (int)$error->getCode() . ', ' . json_encode( "Upload FAILED: " . $error->getMessage() ) . ', ' . json_encode( $file_name ) . ');';
            echo '</script>';
        }
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
            case 1: throw new Exception( 'The uploaded file exceeds the upload_max_filesize directive in php.ini', 5 );
            case 2: throw new Exception( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 6 );
            case 3: throw new Exception( 'The uploaded file was only partially uploaded', 7 );
            case 4: throw new Exception( 'No file was uploaded', 8 );
            case 6: throw new Exception( 'Missing a temporary folder', 9 );
            case 7: throw new Exception( 'Failed to write file to disk', 10 );
            case 8: throw new Exception( 'File upload stopped by extension.', 11 );
            case 0: return true;
            default: throw new Exception( 'Unknown upload error', 12 );
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
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions);
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
    
        /** @var */ $vgSettings = (new SettingsModel())->get_settingsdata();
        /** @var string $uploadsDir - Absolute path to uploaded videofiles */
        $uploadsDir = ABSPATH . $vgSettings->uploads;
    
        /** @var string $file_name */
        $file_name = self::generateTempFilename($file['name'], $uploadsDir);
    
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));	
    
        // File move to destination directory
        $amazon_s3_bucket_setting = $wpdb->get_var("SELECT player_colors FROM ".$wpdb->prefix."ntube_settings");
        $player_colors = unserialize($amazon_s3_bucket_setting);
        if($extension !== 'srt' && $player_colors['amazonbuckets_enable'] && $player_colors['amazonbuckets_name'] ){
            $s3bucket_name  =  $player_colors['amazonbuckets_name'];
            require_once(__DIR__ . '/../../helper/s3_config.php');
            if($s3->putObjectFile($file['tmp_name'],$s3bucket_name,$file_name,S3::ACL_PUBLIC_READ)){
                $file_name = 'http://'.$s3bucket_name.'.s3.amazonaws.com/'.$file_name;
            }else{
                throw new Exception('Unknown Error Occured', 4);
            }
            // End Amazon S3 bucket  storage data
        } else {	  
            if( !move_uploaded_file( $file['tmp_name'], $uploadsDir . '/' . $file_name ) ) {
                throw new Exception('Unknown Error Occured', 4);
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
     * @param string $uploadsDir - absolute path to video upload directory
     * @return string - Filename, how to store in videogallery upload directory
     */
    public static function generateTempFilename($originalFilename, $uploadsDir) { 
        // For better orientation in filesystem add part of original filename
        $normalizedFilename = self::normalizeFilename($originalFilename);   
        do {
            $tempFilename =  'temp-' . rand(10000,99999) . '-' . $normalizedFilename;
        } while (file_exists($uploadsDir . '/' . $tempFilename));
        return $tempFilename;
    }

    
    /**
     * Trim filename, reduce diacritics and special chars, switch spaces to '-' char.
     * 
     * @param string $originalFilename - File basename (without path).
     * @return string - Filename, how is best way to store in filesystem.
     */
    public static function normalizeFilename($originalFilename) {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    
        if($extension === 'jpeg'){
            $extension = 'jpg';
        }

        return strtolower(                                          // 6) All in lowecase 
            preg_replace('/[\-]+$/u', '',                           // 5) Remove '-' from string ending (if exists)
                preg_replace('/^[\-]+/u', '',                       // 4) Remove '-' from string beginning (if exists)
                    substr(                                         // 3) Limit filename size to 32 chars
                        preg_replace('/[^A-Za-z0-9]+/u', '-', // 2) Replace special chars to '-'
                            remove_accents(                         // 1) Remove diacritics by global WP function
                                pathinfo($originalFilename, PATHINFO_FILENAME)
                            )
                        ), 0, 30
                    )
                )
            )
        ) . '.' . $extension;
    }

}
