<?php
class iDecorImages {

    /**
     * Get WooCommerce products with no featured image
     */
    public function getProductNoImage($limit) { 
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
            LIMIT $limit;
        ";

        $skus = [];
        $DatabaseData = $wpdb->get_results($sql_query);

        foreach ($DatabaseData as $data) { 
            $skus[] = $data->sku;
        }

        return $skus;
    }

    /**
     * Download images from FTP for given SKUs
     * Returns array of full downloaded file paths
     */
    public function downloadImage($productCodes) {

        if (!is_array($productCodes)) {
            $productCodes = [$productCodes];
        }

        $ip            = get_option('BORN_WOO_FTP_IMAGES_IP', '2.139.176.160');
        $port          = (int) get_option('BORN_WOO_FTP_IMAGES_PORT', '21');
        $username      = get_option('BORN_WOO_FTP_IMAGES_USERNAME', 'jarom_imgs');
        $password      = get_option('BORN_WOO_FTP_IMAGES_PASSWORD', 'Jp85Rtn21lj2y4*');
        $remoteDirPath = rtrim(get_option('BORN_WOO_FTP_IMAGES_PATH', '/'), '/');

        $localDir = ABSPATH . "images/";
        if (!file_exists($localDir)) mkdir($localDir, 0755, true);

        $conn_id = ftp_connect($ip, $port, 30);
        if (!$conn_id) { 
            error_log(" FTP connection failed");
            return []; 
        }

        if (!ftp_login($conn_id, $username, $password)) { 
            ftp_close($conn_id); 
            error_log(" FTP login failed");
            return []; 
        }

        ftp_pasv($conn_id, true);

        $suffixes = ['', '-1', '-2', '-3'];
        $extensions = ['.jpg', '.JPG'];

        $allDownloaded = [];

        foreach ($productCodes as $productCode) {

            // Remove "ID" suffix from Woo SKU for FTP
            $ftpCode = preg_replace('/ID$/', '', $productCode);
            $downloadedBases = [];

            foreach ($suffixes as $suffix) {
                $baseName = $ftpCode . $suffix;

                if (in_array($baseName, $downloadedBases)) continue;

                $found = false;

                foreach ($extensions as $ext) {
                    $filename = $baseName . $ext;
                    $remote_path = $remoteDirPath . '/' . $filename;

                    // Save locally in Woo SKU ID format
                    $newFilename = $productCode . $suffix . '.jpg'; // always lowercase .jpg
                    $local_path  = $localDir . $newFilename;

                    if (ftp_size($conn_id, $remote_path) != -1) {
                        if (ftp_get($conn_id, $local_path, $remote_path, FTP_BINARY)) {
                            $downloadedBases[] = $baseName;
                            $allDownloaded[] = $local_path;
                            error_log(" Downloaded: $newFilename (Woo SKU: $productCode)\n");
                            $found = true;
                            break; // stop checking other extensions
                        } else {
                            error_log(" Failed to download: $filename (Woo SKU: $productCode)\n");
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) {
                    error_log(" File does not exist on FTP: $baseName (Woo SKU: $productCode)\n");
                }
            }
        }

        ftp_close($conn_id);
        return $allDownloaded;
    }


    /**
     * Optimize downloaded JPEG images
     * Accepts array of full file paths
     */
    public function optimizeImage($files) {
        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
                $img = @imagecreatefromjpeg($filePath);
                if ($img) {
                    // Compress to 75% quality (adjustable)
                    imagejpeg($img, $filePath, 75);
                    imagedestroy($img);
                    error_log(" Optimized: " . basename($filePath) . "\n");
                } else {
                   error_log(" Failed to open: " . basename($filePath) . "\n");
                }
            }
        }
    }

    public function uploadImagesToWoo($productCodes) {

        if (!is_array($productCodes)) {
            $productCodes = [$productCodes];
        }

        $localDir = ABSPATH . "images/";

        foreach ($productCodes as $productCode) {

            // Get the Woo product by SKU
            $product_id = wc_get_product_id_by_sku($productCode);
            if (!$product_id) {
                error_log(" Product not found for SKU: $productCode\n");
                continue;
            }

            $imageFiles = [];

            // Possible suffixes (including no suffix)
            $suffixes = ['', '-1', '-2', '-3'];

            foreach ($suffixes as $suffix) {
                $filePath = $localDir . $productCode . $suffix . '.jpg';
                if (file_exists($filePath)) {
                    $imageFiles[] = $filePath;
                }
            }

            if (empty($imageFiles)) {
                error_log(" No images found locally for SKU: $productCode\n");
                continue;
            }

            // Attach the first image as the product featured image
            $featured = array_shift($imageFiles);
            $featured_id = $this->uploadToMediaLibrary($featured, $product_id);
            if ($featured_id) {
                update_post_meta($product_id, '_thumbnail_id', $featured_id);
                error_log(" Featured image set for SKU: $productCode\n");
            }

            // Attach remaining images as product gallery
            $gallery_ids = [];
            foreach ($imageFiles as $img) {
                $id = $this->uploadToMediaLibrary($img, $product_id);
                if ($id) $gallery_ids[] = $id;
            }

            if (!empty($gallery_ids)) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
                error_log(" Gallery images set for SKU: $productCode\n");
            }

            // Optional: mark image status to avoid re-downloading
            update_post_meta($product_id, '_ide_image_status', '1');
        }
    }

    /**
     * Helper function to upload local file to WordPress Media Library
     */
    private function uploadToMediaLibrary($filePath, $post_id = 0) {
        if (!file_exists($filePath)) return false;

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $fileArray = [
            'name'     => basename($filePath),
            'tmp_name' => $filePath
        ];

        // Check file type
        $attachment_id = media_handle_sideload($fileArray, $post_id);

        if (is_wp_error($attachment_id)) {
            error_log(" Failed to upload " . basename($filePath) . "\n");
            return false;
        }

        return $attachment_id;
    }

}