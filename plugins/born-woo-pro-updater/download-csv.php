<?php 

include_once plugin_dir_path(__FILE__) . 'class/csv.php';

// Add a manual page refresh trigger
add_action('init', function() {
    if ( isset($_GET['run_csv_download']) ) { 
        $iDecorCSV = new iDecorCSV();
        $downloadCSV = $iDecorCSV->downloadCSV(); 
    }
});