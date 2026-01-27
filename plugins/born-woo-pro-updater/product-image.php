<?php 

include_once plugin_dir_path(__FILE__) . 'class/images.php';

// Add a manual page refresh trigger
add_action('admin_init', function() {
    // Only run when ?run_image_download=1 is in URL
    if (isset($_GET['run_image_download']) && current_user_can('manage_options')) {

        $iDecorImages = new iDecorImages();

        // 1️⃣ Get products without images
        $getProductNoImage = $iDecorImages->getProductNoImage(1); // adjust limit if needed

        if (empty($getProductNoImage)) {
            error_log('<div class="notice notice-warning"><p>⚠️ No products without images found.</p></div>');
            return;
        }

        // 2️⃣ Download images from FTP in ID format
        $downloadedFiles = $iDecorImages->downloadImage($getProductNoImage);

        if (!empty($downloadedFiles)) {
            // 3️⃣ Optimize downloaded images
            $iDecorImages->optimizeImage($downloadedFiles);

            // 4️⃣ Upload optimized images to WooCommerce
            $iDecorImages->uploadImagesToWoo($getProductNoImage);

            error_log( '<div class="notice notice-success"><p>✅ Images downloaded, optimized, and uploaded to WooCommerce!</p></div>');
        } 
        else {
            error_log( '<div class="notice notice-error"><p>❌ No images were downloaded from FTP.</p></div>');
        }
    }
});
