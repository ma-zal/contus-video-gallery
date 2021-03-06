<?php
/**  
 * Videos Gallery Home page Controller file.
 * Note: Based on 2.7 sourcode by Apptha. With custom changes.
 *
 * @category   Apptha
 * @package    Contus video Gallery
 * @version    2.7.1
 * @author     Apptha Team <developers@contus.in>
 * @author     Martin Zaloudek, zal.cz (as contributor)
 * @copyright  Copyright (C) 2014 Apptha. All rights reserved.
 * @license    GNU General Public License http://www.gnu.org/copyleft/gpl.html 
 */
require_once __DIR__ . '/../models/video.php';      // Class VideoModel
require_once __DIR__ . '/../ajax/videoupload.php';  // Class VgAjaxVideoUpload

if ( class_exists( 'VideoController' ) != true ) {			

	class VideoController extends VideoModel {	

		public $_status;
		public $_msg;
		public $_search;
		public $_videosearchQuery;
		public $_settingsData;
		public $_addnewVideo;
		public $_searchBtn;
		public $_update;
		public $_add;
		public $_del;
		public $_featured;
		public $_orderDirection;
		public $_orderBy;
		public $_adminorder_direction; 

		public function __construct() {					
			parent::__construct();
			$this->_videosearchQuery = filter_input( INPUT_POST, 'videosearchQuery' );
			$this->_addnewVideo		 = filter_input( INPUT_POST, 'add_video' );
			$this->_status			 = filter_input( INPUT_GET, 'status' );
			$this->_searchBtn		 = filter_input( INPUT_POST, 'videosearchbtn' );
			$this->_update			 = filter_input( INPUT_GET, 'update' );
			$this->_add				 = filter_input( INPUT_GET, 'add' );
			$this->_del				 = filter_input( INPUT_GET, 'del' );
			$this->_featured		 = filter_input( INPUT_GET, 'featured' );
			$this->_orderDirection   = filter_input( INPUT_GET, 'order' );
			$this->_adminorder_direction   = $this->get_order_details();
			$this->_orderBy			 = filter_input( INPUT_GET, 'orderby' );
			$this->_settingsData     = $this->get_settingsdata();
		}
        
        
        /**
         *  function for adding video and update status / featured.
         */
		public function add_newvideo() {			
			global $wpdb;
			$match = null; // undefined index $matches error hide 
			if ( isset( $this->_status ) || isset( $this->_featured ) ) { 
				$this->status_update( $this->_videoId, $this->_status, $this->_featured );
			}										## updating status of video ends
			if ( $this->_addnewVideo ) { // If action is add/update video
				$tags_name			 = filter_input( INPUT_POST, 'tags_name' );
				$strip_tags_name     = strtolower( stripslashes( $tags_name ) );
				$ambersand_tags_name = preg_replace( '/[&:\s]+/i', '-', $strip_tags_name );
				$spl_tags_name		 = preg_replace( '/[#!@$%^.,:;\/&*(  ){}\"\'\[\]<>|?]+/i', '', $ambersand_tags_name );
				$seo_tags_name		 = preg_replace( '/---|--+/i', '-', $spl_tags_name );
				$streamname			 = filter_input( INPUT_POST, 'streamerpath-value' );
				$videoLinkurl		 = filter_input( INPUT_POST, 'youtube-value' );
				$pieces				 = explode( ',', $_POST['hid'] );
				$act_playlist = '';
				if ( ! empty( $_POST['playlist'] ) ) {
					$act_playlist = $_POST['playlist'];
				}
				$videoFeatured    = filter_input( INPUT_POST, 'feature' );
				$videoDownload    = filter_input( INPUT_POST, 'download' );
				$videomidrollads  = filter_input( INPUT_POST, 'midrollads' );
				$videoimaad       = filter_input( INPUT_POST, 'imaad' );
				$videoPostrollads = filter_input( INPUT_POST, 'postrollads' );
				$videoPrerollads  = filter_input( INPUT_POST, 'prerollads' );
				$google_adsense   = filter_input( INPUT_POST, 'googleadsense');
				$google_adsense_value   = filter_input( INPUT_POST, 'google_adsense_value');

				$dir              = dirname( plugin_basename( __FILE__ ) );
				$dirExp    = explode( '/', $dir );
				$dirPage   = $dirExp[0];
				$srt_path1 = str_replace( 'plugins', 'uploads/videogallery/', APPTHA_VGALLERY_BASEDIR );
				$srt_path  = str_replace( $dirPage, '', $srt_path1 );

				$ordering       = $this->_wpdb->get_var( 'SELECT count( ordering ) FROM ' . $wpdb->prefix . 'hdflvvideoshare' );
                /** POST value: normalvideoform-value - video filename without path */
				$video1         = filter_input( INPUT_POST, 'normalvideoform-value' );
                /** POST value: hdvideoform-value */
				$video2         = filter_input( INPUT_POST, 'hdvideoform-value' );
                /** POST value: thumbimageform-value - thumbnail filename without path*/
				$img1           = filter_input( INPUT_POST, 'thumbimageform-value' );
				$img2           = filter_input( INPUT_POST, 'previewimageform-value' );
				$subtitle1      = filter_input( INPUT_POST, 'subtitle1form-value' );
				$subtitle2      = filter_input( INPUT_POST, 'subtitle2form-value' );
				$subtitle_lang1 = filter_input( INPUT_POST, 'subtitle_lang1' );
				$subtitle_lang2 = filter_input( INPUT_POST, 'subtitle_lang2' );
				$member_id      = filter_input( INPUT_POST, 'member_id' );
                $amazon_buckets = filter_input(INPUT_POST , 'amazon_buckets');
                $act_filepath = null;
                $duration = null;
                
        		if ( $videoLinkurl != '' ) { // Entered Video URL
					if ( preg_match( '#https?://#', $videoLinkurl ) === 0 ) {
						$videoLinkurl = 'http://' . $videoLinkurl;
					}
					$act_filepath = addslashes( trim( $videoLinkurl ) );
					// Note: file_type = '1';

					if ( strpos( $act_filepath, 'youtube' ) > 0 ) {
						$imgstr     = explode( 'v=', $act_filepath );
						$imgval     = explode( '&', $imgstr[1] );
						$match      = $imgval[0];
					} else if ( strpos( $act_filepath, 'youtu.be' ) > 0 ) {
						$imgstr       = explode( '/', $act_filepath );
						$match        = $imgstr[3];
						$act_filepath = 'http://www.youtube.com/watch?v=' . $imgstr[3];
					}
					$youtube_data = $this->hd_getsingleyoutubevideo( $match );
					$sec          = $youtube_data['duration']['SECONDS'];
					$duration     = $this->converttime( $sec );

				} else { // Entered video file 
					// Note: file_type = '2';
					if( isset($_POST['custom_url']) ){
    					$act_filepath  = addslashes( trim( $_POST['customurl'] ) );
					}else{
						$act_filepath = '';
					}

                    // Get duration by FFMPEG
					$ffmpeg_path   = $this->_settingsData->ffmpeg_path;
                    if (!file_exists($ffmpeg_path)) {
                        trigger_error('FFMPEG binary file not found at ' . $ffmpeg_path . ', where is expected.', E_USER_ERROR);
                    }
					ob_start();
                    passthru($ffmpeg_path . ' -i "' . $srt_path . $video1 . '" 2>&1');
					$get_duration = ob_get_contents();
					ob_end_clean();
					$search   = '/Duration: (.*?),/';
					$duration = preg_match( $search, $get_duration, $matches, PREG_OFFSET_CAPTURE, 3 );
					if ( ! empty( $duration ) ) {
						$duration_array = explode( ':', $matches[1][0] );
						$sec            = ceil( $duration_array[0] * 3600 + $duration_array[1] * 60 + $duration_array[2] );
						$duration       = $this->converttime( $sec );
					} else {
						$duration = '0:00';
					}
					
					// Get previous version of Video DB data
    				/*@var*/ $previousVideoDetails = null;
    				if ($this->_videoId) { // Edit existing video item
    				    $previousVideoDetails = $wpdb->get_row('SELECT slug, media_id, image, file FROM ' . $wpdb->prefix . 'hdflvvideoshare WHERE media_id =' . $this->_videoId);
    				}
    				
    				// Detect if new files has been uploaded
    				if ($this->_videoId) { // Edit existing video item
    				    /*@var*/ $isUploadedNewVideo = (!empty($video1) && $video1 !== pathinfo($previousVideoDetails->file, PATHINFO_BASENAME));
    				    /*@var*/ $isUploadedNewImage = (!empty($img1) && $img1 !== pathinfo($previousVideoDetails->image, PATHINFO_BASENAME));
    				} else {
    				    /*@var*/ $isUploadedNewVideo = (!empty($video1));
    				    /*@var*/ $isUploadedNewImage = (!empty($img1));
    				}
    				
    				// Remove the old files if new video or image has been uploaded
    				if ($this->_videoId) { // Edit existing video item
    				    if ($isUploadedNewVideo && !empty($previousVideoDetails->file)) {
    				        // Delete the old video
    				        echo '<p>Deleting old video ' . pathinfo($previousVideoDetails->file, PATHINFO_BASENAME) . ' ...</p>';
    				        unlink($srt_path . $previousVideoDetails->file);
    				    }
    				    // Delete the old thumbnail
    				    if (($isUploadedNewVideo || $isUploadedNewImage) && !empty($previousVideoDetails->file)) {
    				        echo '<p>Deleting old thumbnail ' . pathinfo($previousVideoDetails->image, PATHINFO_BASENAME) . ' ...</p>';
    				        unlink($srt_path . $previousVideoDetails->image);
    				        if (!$isUploadedNewImage) {
    				            // No new thumbnail uploaded. Will be generated automatically 
    				            $img1 = null; // Will be generated the new one
    				        }
    				    }
    				}
					
    
                    // Rename newly uploaded "temp" files to filaname, that match with Video title
                    /** @var int $vid - VideoID (db row index) of Media, that video/image will be assocated to. */
                    $vid = null; 
                    if ($this->_videoId) {
                        $vid = $this->_videoId;
                    } else {
                        // In case adding the new video, the media item db row does not exists yet.
                        // ID is autoincrement value, so we are trying found last item. 
                        $last_vid = $wpdb->get_var('select MAX( media_id ) from ' . $wpdb->prefix . 'hdflvvideoshare');
                        $vid = ((empty($last_vid) ? 0 : $last_vid) + 1);
                    }
                    if ($isUploadedNewVideo) { // if video file has been sent
                        $old_video1 = $video1;

                        // Generate new filename of video
                        do {
                            $video1 = VgAjaxVideoUpload::normalizeFilename(
                                $vid . '-' . 
                                filter_input(INPUT_POST, 'name') . 
                                (isset($postfix) ? '-' . $postfix : '') . 
                                '.' . pathinfo($old_video1, PATHINFO_EXTENSION)
                            );
                            /**@var*/ $postfix = isset($postfix) ? $postfix + 1 : 2;
                        } while (file_exists($srt_path . $video1));   // Note: Check if same filename does not exists

                        // Rename file
                        rename($srt_path . $old_video1, $srt_path . $video1);
                        echo '<p>Updating new video name: ' . $old_video1 . ' -> ' . $video1 . '</p>';
                        unset($old_video1);
                    }
                    if ($isUploadedNewImage) { // if image thumbnail file has been sent
                        $old_img1 = $img1;

                        // Generate new filename of image thumbnail
                        do {
                            $img1 = VgAjaxVideoUpload::normalizeFilename(
                                $vid . '-' . 
                                filter_input(INPUT_POST, 'name') . 
                                (isset($postfix) ? '-' . $postfix : '') . 
                                '.' . pathinfo($old_img1, PATHINFO_EXTENSION)
                            );
                            /**@var*/ $postfix = isset($postfix) ? $postfix + 1 : 2;
                        } while (file_exists($srt_path . $img1));   // Note: Check if same filename does not exists

                        // Rename file
                        rename($srt_path . $old_img1, $srt_path . $img1);
                        echo '<p>Updating new thumbnail name: ' . $old_img1 . ' -> ' . $img1 . '</p>';
                        unset($old_img1);
                    }
				}

				/**@var*/ $act_image = addslashes( trim( $_POST['customurl'] ) );
				/**@var*/ $act_hdpath = '';
			    /**@var*/ $act_opimage = '';

				if ( ! empty( $act_filepath ) ) {
					if ( strpos( $act_filepath, 'youtube' ) > 0 || strpos( $act_filepath, 'youtu.be' ) > 0 ) {
						if ( strpos( $act_filepath, 'youtube' ) > 0 ) {
							$imgstr = explode( 'v=', $act_filepath );
							$imgval = explode( '&', $imgstr[1] );
							$match  = $imgval[0];
						} else if ( strpos( $act_filepath, 'youtu.be' ) > 0 ) {
							$imgstr = explode( '/', $act_filepath );
							$match  = $imgstr[3];
							$act_filepath = 'http://www.youtube.com/watch?v=' . $imgstr[3];
						}

						$act_image    = 'http://i3.ytimg.com/vi/' . $match . '/mqdefault.jpg';
						$act_opimage  = 'http://i3.ytimg.com/vi/' . $match . '/maxresdefault.jpg';
						$youtube_data = $this->hd_getsingleyoutubevideo( $match );
                        if (!$youtube_data) {
							$this->render_error( __( 'Could not retrieve Youtube video information', 'hdflvvideoshare' ) );
						}
					}else if ( strpos( $act_filepath, 'dailymotion' ) > 0 ) {			  ## check video url is dailymotion
						$split     = explode( '/', $act_filepath );
						$split_id  = explode( '_', $split[4] );
						$act_image = $act_opimage = 'http://www.dailymotion.com/thumbnail/video/' . $split_id[0];
						// Note: file_type = '1';
					} else if ( strpos( $act_filepath, 'viddler' ) > 0 ) {					## check video url is viddler
						$imgstr    = explode( '/', $act_filepath );
						$act_image = $act_opimage = 'http://cdn-thumbs.viddler.com/thumbnail_2_' . $imgstr[4] . '_v1.jpg';
						// Note: file_type = '1';
					} 
				} else {
                    if ($video1 != '') {
						$act_filepath = $video1;
                    }
                    if ($video2 != '') {
						$act_hdpath = $video2;
                    }
                    if ($img1 != '') {
						$act_image = $img1;
                    }
                    if ($img2 != '') {
						$act_opimage = $img2;
				    }
                }

				if ( ! empty( $streamname ) ) {
					// Note: file_type   = '4';

					$thumb_image    = filter_input(INPUT_POST ,'filepath3');
					$act_image      = $thumb_image;
					$act_opimage    = $thumb_image;
				}
//				if ( ! empty( filter_input( INPUT_POST, 'embed_code' ) ) ) {
//					Note: file_type = '5';
//				}
				if ( empty($member_id ) ) {
					$current_user = wp_get_current_user();
					$member_id    = $current_user->ID;
				}			
                if (filter_input(INPUT_POST, 'filetypevalue') == 3) {
				  $act_filepath = $_POST['customurl'];
				  $act_image    = $_POST['customimage'];
				  $act_opimage  = $_POST['custompreimage'];
				  $act_hdpath   = $_POST['customhd'];
				}
                if (filter_input(INPUT_POST, 'filetypevalue') == 4) {
					$act_filepath  =  filter_input(INPUT_POST , 'customurl');
					$act_hdpath    =  filter_input(INPUT_POST ,'customhd');
					$act_image     =  filter_input(INPUT_POST ,'customimage');
					$act_opimage   =  filter_input(INPUT_POST , 'custompreimage');
				}
				$videoData = array(
                    'name'              => filter_input(INPUT_POST, 'name'),
                    'description'       => filter_input(INPUT_POST, 'description'),
                    'embedcode'         => filter_input(INPUT_POST, 'embed_code'),
					'file'				=> $act_filepath,
                    'file_type'         => filter_input(INPUT_POST, 'filetypevalue'),
					'member_id'			=> $member_id,
					'duration'			=> $duration,
					'hdfile'			=> $act_hdpath,
					'streamer_path'		=> $streamname,
                    'islive'            => filter_input(INPUT_POST, 'islive-value'),
					'image'				=> $act_image,
					'opimage'			=> $act_opimage,
					'srtfile1'			=> $subtitle1,
					'srtfile2'			=> $subtitle2,
					'subtitle_lang1'	=> $subtitle_lang1,
					'subtitle_lang2'	=> $subtitle_lang2,
					'link'				=> $videoLinkurl,
					'featured'			=> $videoFeatured,
					'download'			=> $videoDownload,
					'postrollads'		=> $videoPostrollads,
					'midrollads'		=> $videomidrollads,
					'imaad'				=> $videoimaad,
					'prerollads'		=> $videoPrerollads,
                    'publish'           => filter_input(INPUT_POST, 'publish'),
				    'google_adsense'	=> $google_adsense,
					'google_adsense_value'=> $google_adsense_value,
					'amazon_buckets'    =>$amazon_buckets,		
				);     				
					$videoData['post_date'] = date( 'Y-m-d H:i:s' );
				if( empty ( $this->_videoId ) ) {	
					$videoData['ordering']  = $ordering;
					$videoData['slug']      = '';
				}
                
                if ($this->_videoId) { // Edit existing video item
                    $slug_id = $previousVideoDetails->slug;
					$videoData['slug'] = $slug_id;
					$this->video_update( $videoData, $this->_videoId);
                        
                    /** @var */ $new_subtitle1 = '';
                    if(!empty($subtitle1))
                    {
                        $sub_title_path1 = $srt_path.$subtitle1;
                        if(file_exists($sub_title_path1))
                        {
                            $new_subtitle1 = $this->_videoId.'_'.$subtitle_lang1.'.srt';
                            rename($sub_title_path1,$srt_path.$new_subtitle1);
                        }
                        else
                        {
                        $new_subtitle1 = $subtitle1;
                        }
                    }
                        
                    /** @var */ $new_subtitle2 = '';
                    if(!empty($subtitle2))
                    {
                        $sub_title_path2 = $srt_path.$subtitle2;
                    
                        if(file_exists($sub_title_path2))
                        {
                            $new_subtitle2 = $this->_videoId.'_'.$subtitle_lang2.'.srt';
                            rename($sub_title_path2,$srt_path.$new_subtitle2);
                        }
                        else
                        {
                        $new_subtitle2 = $subtitle2;
                        }
                    }
                        
					$wpdb->query( ' UPDATE ' . $wpdb->prefix . 'hdflvvideoshare SET srtfile1= "'.$new_subtitle1.'",srtfile2= "'.$new_subtitle2.'" , subtitle_lang1="'.$subtitle_lang1.'" ,  subtitle_lang2 ="'.$subtitle_lang2.'"  WHERE vid = '.$this->_videoId );

					if ( $this->_videoId && is_array( $act_playlist ) ) {
						$old_playlist = $wpdb->get_col( ' SELECT playlist_id FROM ' . $wpdb->prefix . 'hdflvvideoshare_med2play WHERE media_id ='. $this->_videoId );
						if ( ! $old_playlist ) {
							$old_playlist = array();
						} else {
							$old_playlist = array_unique( $old_playlist );
						}

						##  Delete any ?
						$delete_list = array_diff( $old_playlist, $act_playlist );
						if ( $delete_list ) {
							foreach ( $delete_list as $del ) {
								$wpdb->query( ' DELETE FROM ' . $wpdb->prefix . 'hdflvvideoshare_med2play WHERE playlist_id = ' . $del . ' AND media_id ='. $this->_videoId );
							}
						}

						$add_list = array_diff( $act_playlist, $old_playlist );
						if ( $add_list ) {
							foreach ( $add_list as $new_list ) {
								$new_list1 = $new_list - 1;
								$wpdb->query( ' INSERT INTO ' . $wpdb->prefix . 'hdflvvideoshare_med2play ( media_id,playlist_id,sorder ) VALUES ( '.$this->_videoId.', ' . $new_list . ', 0 )' );
							}
						}
						$i = 0;
						foreach ( $pieces as $new_list ) {
							$wpdb->query( ' UPDATE ' . $wpdb->prefix . 'hdflvvideoshare_med2play SET sorder= 0 WHERE media_id = '.$this->_videoId.' and playlist_id = '.$new_list );
							$i++;
						}
					}
					$insert_tags_name = $this->_wpdb->get_var( 'SELECT media_id FROM ' . $wpdb->prefix . 'hdflvvideoshare_tags WHERE media_id ='. $this->_videoId );
					if ( empty( $insert_tags_name ) ) {
						$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'hdflvvideoshare_tags ( media_id,tags_name,seo_name ) VALUES ( '.$this->_videoId.', "'.$tags_name.'", "'.$seo_tags_name.'" )' );
					} else {
						$wpdb->query( 'UPDATE ' . $wpdb->prefix . 'hdflvvideoshare_tags SET tags_name="'.$tags_name.'",seo_name="'.$seo_tags_name.'" WHERE media_id = '.$this->_videoId );
					}
					$this->admin_redirect( 'admin.php?page=newvideo&videoId=' . $this->_videoId . '&update=1' );

                } else {  // Add a new video
					$insertflag = $this->insert_video( $videoData);
					if ( $insertflag != 0 ) {

						/** @var */ $new_subtitle1 = '';
						if ( ! empty( $subtitle1 ) ) {
							$new_subtitle1  =  $insertflag.'_'.$subtitle_lang1.$subtitle1;
							rename( $srt_path.$subtitle1 , $new_subtitle1 ); 						
						}

						/** @var */ $new_subtitle2 = '';                            
						if ( ! empty( $subtitle2 ) ) {
							$new_subtitle2 = $insertflag.'_'.$subtitle_lang2.$subtitle2; 
							rename($srt_path.$subtitle2 , $new_subtitle2 ); 
						}
						$wpdb->query( ' UPDATE ' . $wpdb->prefix . 'hdflvvideoshare SET subtitle_lang1 = "'.$subtitle_lang1.'" , subtitle_lang2 = "'.$subtitle_lang2.'" , srtfile1= "'.$new_subtitle1.'",srtfile2= "'.$new_subtitle2.'" WHERE vid = '.$insertflag );

						if ( ! empty( $tags_name ) ) {
							$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'hdflvvideoshare_tags ( media_id,tags_name,seo_name ) VALUES ( '.$insertflag.', "'.$tags_name.'", "'.$seo_tags_name.'" )' );
						}

						$video_aid = $insertflag;
						if ( $video_aid && is_array( $act_playlist ) ) {
							$add_list = array_diff( $act_playlist, array() );

							if ( $add_list ) {
								foreach ( $add_list as $new_list ) {
									$new_list1 = $new_list - 1;
									$wpdb->query( ' INSERT INTO ' . $wpdb->prefix . 'hdflvvideoshare_med2play ( media_id,playlist_id,sorder ) VALUES ( '.$video_aid.', '.$new_list.', 0 )' );
								}
							}
							$i = 0;
							foreach ( $pieces as $new_list ) {
								$wpdb->query( ' UPDATE ' . $wpdb->prefix . 'hdflvvideoshare_med2play SET sorder= 0 WHERE media_id = '.$video_aid.' and playlist_id = '.$new_list );
								$i++;
							}
						}
					}
					if ( ! $insertflag ) {
						$this->admin_redirect( 'admin.php?page=video&add=0' );
					} else {
						$this->admin_redirect( 'admin.php?page=video&add=1' );
					}
				}											## adding video else ends
			}
		}

		public function render_error( $message ) {
			?>
			<div class="wrap"><h2>&nbsp;</h2>
				<div class="error" id="error">
					<p><strong><?php echo balanceTags( $message ); ?></strong></p>
				</div>
			</div>
			<?php
		}
		
		public function admin_redirect( $url ) {								## admin redirection url function starts
			echo '<script>window.open( "' . $url . '","_top" )</script>';
		}

		## admin redirection url function ends
		public function video_data() {										## getting video data function starts
			$orderBy = array( 'id', 'title', 'author', 'category', 'fea', 'publish', 'date', 'ordering' );
			$order   = '';
			if ( isset( $this->_orderBy ) && in_array( $this->_orderBy, $orderBy ) ) {
				$order = $this->_orderBy;
			}

			switch ( $order ) {
				case 'id':
					$order = 'vid';
					break;

				case 'title':
					$order = 'name';
					break;

				case 'author':
					$order = 'u.display_name';
					break;
				case 'category':
					$order = 'pl.playlist_name';
					break;

				case 'fea':
					$order = 'featured';
					break;

				case 'date':
					$order = 'post_date';
					break;

				case 'publish':
					$order = 'publish';
					break;

				case 'ordering':
					$order = 'ordering';
					break;

				default:
					$order = 'vid';
					$this->_orderDirection = 'DESC';
			}
			return $this->get_videodata( $this->_videosearchQuery, $this->_searchBtn, $order, $this->_orderDirection );
		}
        
		/** 
		 * function for adding video ends
		 *
	     * @param $youtube_media
		 * @return mixed
		 */
		function hd_getsingleyoutubevideo( $youtube_media ) {
		
			if ( $youtube_media == '' ) {
				return null;
			}
			$url = 'http://gdata.youtube.com/feeds/api/videos/' . $youtube_media;
			$ytb = hd_parseyoutubedetails( hd_getyoutubepage( $url ) );
			return $ytb[0];
		}
		
		## getting video data function ends
		function converttime( $sec ) {
			$hms	 = $padHours = '';
			$hours   = intval( intval( $sec ) / 3600 );
			$hms    .= (  $padHours ) ? str_pad( $hours, 2, '0', STR_PAD_LEFT ) . ':' : $hours . ':';
			if ( $hms == '0:' ) {
				$hms = '';
			}
			$minutes = intval( ( $sec / 60 ) % 60 );
			$hms    .= str_pad( $minutes, 1, '0', STR_PAD_LEFT ) . ':';
			$seconds = intval( $sec % 60 );
			$hms    .= str_pad( $seconds, 2, '0', STR_PAD_LEFT );
			##  done!
			return $hms;
		}

		public function get_message() {										## displaying database message function starts
			if ( isset( $this->_update ) && $this->_update == '1' ) {
				$this->_msg = 'Video Updated Successfully ...';
			} else if ( $this->_update == '0' ) {
				$this->_msg = 'Video Not Updated  Successfully ...';
			}

			if ( isset( $this->_add ) && $this->_add == '1' ) {
				$this->_msg = 'Video Added Successfully ...';
			}

			if ( isset( $this->_del ) && $this->_del == '1' ) {
				$this->_msg = 'Video Deleted Successfully ...';
			}
			if ( isset( $this->_status ) && $this->_status == '1' ) {
				$this->_msg = 'Video Published Successfully ...';
			} else if ( $this->_status == '0' ) {
				$this->_msg = 'Video Unpublished Successfully ...';
			}
			if ( isset( $this->_featured ) && $this->_featured == '1' ) {
				$this->_msg = 'Video set as Featured Video Successfully ...';
			} else if ( $this->_featured == '0' ) {
				$this->_msg = 'Video set as Unfeatured Video Successfully...';
			}

			return $this->_msg;
		}

		/**
		 * Bulk Action Publish, Featured, Delete, Unfeatured, Unpublish function
		 */
		public function get_delete() {											
			$videoApply		 = filter_input( INPUT_POST, 'videoapply' );
			$videoActionup   = filter_input( INPUT_POST, 'videoactionup' );
			$videoActiondown = filter_input( INPUT_POST, 'videoactiondown' );
			$videocheckId    = filter_input( INPUT_POST, 'video_id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
			if ( isset( $videoApply ) ) {											
				if ( $videoActionup =='videodelete' || $videoActiondown == 'videodelete' ) {		
					if ( is_array( $videocheckId ) ) {
						$videoId    = implode( ',', $videocheckId );
						$deleteflag = $this->video_delete( $videoId );
						if ( ! $deleteflag ) {
							$this->admin_redirect( 'admin.php?page=video&del=0' );
						} else {
							$this->admin_redirect( 'admin.php?page=video&del=1' );
						}
					}
				} elseif($videoActionup =="videopublish" || $videoActiondown == 'videopublish' || $videoActionup =="videounpublish" || $videoActiondown == "videounpublish"){
					if( is_array($videocheckId) ){
                      $videoId = implode(',',$videocheckId);
                      if($videoActionup == "videopublish" || $videoActiondown =="videopublish"){
                        $publishflag = $this->video_multipublish($videoId);
                        if($publishflag){
                      	   $this->admin_redirect( 'admin.php?page=video&status=1' );
                        }
                      }
                      if($videoActionup == "videounpublish" || $videoActiondown =="videounpublish"){
                      	$unpublishflag = $this->video_multiunpublish($videoId);
                      	if($unpublishflag){
                      		$this->admin_redirect( 'admin.php?page=video&status=0' );
                      	}
                      }
						
					}
				} elseif($videoActionup =="videofeatured" || $videoActiondown == 'videofeatured' || $videoActionup =="videounfeatured" || $videoActiondown == 'videounfeatured'){
					if( is_array($videocheckId) ){
                      $videoId = implode(',',$videocheckId);
                      if($videoActionup == "videofeatured" || $videoActiondown == "videofeatured"){
                       $publishflag = $this->video_multifeatured($videoId);
                       if($publishflag){
                      	$this->admin_redirect( 'admin.php?page=video&featured=1' );
                       }
                      }
                      if($videoActionup == "videounfeatured" || $videoActiondown == "videounfeatured"){
                      $publishflag = $this->video_multiunfeatured($videoId);
                       if($publishflag){
                      	$this->admin_redirect( 'admin.php?page=video&featured=0' );
                       }
                      }
						
					}
				}																
			}																	
		}
																				
	}
																				
}																				

$videoOBJ = new VideoController();
$videoOBJ->add_newvideo();
$videoId = $videoOBJ->_videoId;
$videoOBJ->get_delete();
$gridVideo		  = $videoOBJ->video_data();
$videosearchQuery = $videoOBJ->_videosearchQuery;
$searchBtn		  = $videoOBJ->_searchBtn;
$Video_count	  = $videoOBJ->video_count( $videosearchQuery, $searchBtn );
if ( ! empty ( $videoId ) ) {
	$videoEdit = $videoOBJ->video_edit( $videoId );
} else {
	$videoEdit = '';
}
$displayMsg   = $videoOBJ->get_message( );
$searchMsg		  = $videoOBJ->_videosearchQuery;
$settingsGrid = $videoOBJ->_settingsData;
$adminPage    = filter_input( INPUT_GET, 'page' );
if ( $adminPage == 'video' ) {													## including video form if starts
	require_once( __DIR__ . '/../views/video/video.php' );
}																				## including video form if starts
else if ( $adminPage == 'newvideo' ) {											## including newvideo ad form if starts
	require_once( __DIR__ . '/../views/video/addvideo.php' );
}																				## including newvideo ad form if ends