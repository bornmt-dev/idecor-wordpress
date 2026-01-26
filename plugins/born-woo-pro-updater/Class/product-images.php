<?php
use League\Csv\Reader;
use League\Csv\Statement;

function upload_product_image($image_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Upload the image and attach it to the product
    $image = media_sideload_image($image_url, $post_id, null, 'id');

    if (is_wp_error($image)) {
        error_log("Image upload failed for post $post_id: " . $image->get_error_message());
        return null;
    }

    return (int) $image;
}


class iDecorProductImage {
    
    public function __construct( ) {}

    public function attach_images_from_ftp () {  
        error_log("START: attach_images_from_ftp() ");  

        $ip = get_option('BORN_WOO_FTP_IMAGES_IP', '');
        $port = get_option('BORN_WOO_FTP_IMAGES_PORT', '');
        $username = get_option('BORN_WOO_FTP_IMAGES_USERNAME', '');
        $password = get_option('BORN_WOO_FTP_IMAGES_PASSWORD', '');
        $remoteDirPath = get_option('BORN_WOO_FTP_IMAGES_PATH', '');

        $localDir = ABSPATH . "images/";

        if (!class_exists('WooCommerce')) {
            error_log("WooCommerce is not active or not properly loaded. ");
            return false;
        }

        // Ensure local directory exists
        if (!file_exists($localDir)) {
            mkdir($localDir, 0777, true);
        }
        else {
            chmod($localDir, 0777);
        }

        /**
        * FTP CONNECTIONS
        */

        error_log("START: FTP CONNECTIONS ");
        // Connect to the FTP server and login once
        $connection = ftp_connect($ip, $port, 90); // Increased timeout to 90 seconds
        if (!$connection) {
            error_log("Failed to connect to FTP server $ip on port $port");
            return false;
        }

        if (!ftp_login($connection, $username, $password)) {
            error_log("Failed to login to FTP server with username $username");
            ftp_close($connection);
            return false;
        }

        // Enable passive mode
        ftp_pasv($connection, true);

        // Fetch the directory file list once
        $fileList = ftp_nlist($connection, $remoteDirPath);
        if ($fileList === false) {
            error_log("Failed to fetch file list from FTP directory $remoteDirPath");
            ftp_close($connection);
            return false;
        }

        global $wpdb;
        $wp = $wpdb->prefix;

        $sql_query = "
        SELECT p.ID, p.post_title, sku.meta_value AS sku 
        FROM {$wp}posts AS p 
        LEFT JOIN {$wp}postmeta AS pm 
            ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id' 
        LEFT JOIN {$wp}postmeta AS sku 
            ON p.ID = sku.post_id AND sku.meta_key = '_sku' 
        LEFT JOIN {$wp}postmeta AS img_status
            ON p.ID = img_status.post_id AND img_status.meta_key = '_ide_image_status'
        WHERE p.post_type = 'product' 
        AND p.post_status = 'publish' 
        AND (pm.meta_value IS NULL OR pm.meta_value = '') 
        AND (img_status.meta_value IS NULL OR img_status.meta_value != '1')
        LIMIT 1;
        ";
    
        $DatabaseData = $wpdb->get_results($sql_query);
        foreach ( $DatabaseData as $data) { 

            $sku = $data->sku;
            $post_id = $data->ID;
            $sku_without_id = substr($sku, 0, -2);

            $imagesFileExtension = [[]];
            $imagesFileExtension[] = [
                [
                    "$sku.jpg",
                    "$sku_without_id.jpg"
                ],
                [
                    "$sku.JPG",
                    "$sku_without_id.JPG" 
                ], 
                [
                    "$sku-1.jpg",
                    "$sku_without_id-1.jpg"
                ],
                [
                    "$sku-1.JPG",
                    "$sku_without_id-1.JPG"
                ],
                [
                    "$sku-2.jpg",
                    "$sku_without_id-2.jpg"
                ],
                [
                    "$sku-2.JPG",
                    "$sku_without_id-2.JPG"
                ]
            ];
            
            error_log("START: Download and optimize images ");
            $image_sources = [];
            foreach ($imagesFileExtension as $group) { // Loop through each group
                foreach ($group as $file) { // Loop through each file pair
    
                    $FileImageWithID = $file[0];
                    $FileImageWithoutID = $file[1];
    
                    $remoteFileWithID = $remoteDirPath . $FileImageWithID;
                    $remoteFileWithoutID = $remoteDirPath . $FileImageWithoutID;
                    $downloadedPathFile = $localDir . $FileImageWithID;
                  
                    // Check if the file exists in the cached file list
    
                    if (in_array($remoteFileWithoutID, $fileList)) {
                        if ( ftp_get($connection, $downloadedPathFile, $remoteFileWithoutID, FTP_BINARY) ) {

                            // Define input and output file paths
                            $GD_input_path = $downloadedPathFile;
    
                            // Check if the file exists
                            if (!file_exists($GD_input_path)) {
                                continue;
                            }
    
                            $image_source = explode(".", $FileImageWithID);
                            $image_sources[] = $image_source[0] . ".jpg";
                            $image_sources[] = $image_source[0] . ".JPG";
    
                            error_log("Downloaded: " . $downloadedPathFile);
                        }
                        else {
                            error_log("NOT Downloaded : " . $remoteFileWithoutID);
                        }
                    } 
                    else {
                        error_log("File not found: " . $FileImageWithoutID);
                    }
                }
            }
    
            error_log("END: Download and optimize images() "); 
    
            error_log("START: Delete current Woo Product attached images ");
            $product = wc_get_product($post_id);
            $attachment_ids = $product->get_gallery_image_ids();
            $featured_image_id = $product->get_image_id();
            if ($featured_image_id) {
                $attachment_ids[] = $featured_image_id;
            }
            foreach ($attachment_ids as $attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
            error_log("END: Delete current Woo Product attached images ");
    
            add_filter('https_ssl_verify', '__return_false');
            add_filter('https_local_ssl_verify', '__return_false');
    
            error_log("START: Upload images to Woo Product ");
            $gallery_ids = [];
            $count = 0; 
 
            foreach ($image_sources as $image) { 
    
                $image_url = home_url()."/"."images/".$image;
                
                if (file_exists($localDir.$image) && $count == 0 ) { 
                    $image_id = upload_product_image($image_url, $post_id);
                    $product->set_image_id($image_id);
                    $count++;
                }
                else  {
                    $image_id = upload_product_image($image_url, $post_id);
                    $gallery_ids[] = $image_id;
                  
                }
            }
            if ( $gallery_ids ) { 
                $product->set_gallery_image_ids($gallery_ids);
            }
    
            $product->save();
            update_post_meta( $product->get_id(), '_ide_image_status', 1 );

            unset($product, $gallery_ids, $image_sources, $attachment_ids, $imagesFileExtension);
            gc_collect_cycles();
            error_log("END: Upload images to Woo Product ");

        }

        ftp_close($connection);
        unset($product, $DatabaseData, $wpdb);
        gc_collect_cycles();

        error_log("START: Delete temp images ");
        
        $getAllFiles = glob($localDir."/" . '*.{jpg,JPG,Webp,webp}', GLOB_BRACE);
        foreach ($getAllFiles as $file) {
            if (file_exists($file)) {
                unlink($file);  
            }
        }

        error_log("END: Delete temp images ");
    }
}
?>