<?php
use phpseclib3\Net\SFTP;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;

function initializeRemoteConnection($protocol, $ip, $port, $username, $password) {
   // error_log("Attempting to connect with protocol: $protocol");

    if ($protocol === 'sftp') {
        try {
            $remote = new SFTP($ip, $port);
            if ($remote->login($username, $password)) {
              //  error_log("Connected successfully using SFTP.");
                return $remote;
            } else {
              //  error_log("Failed to connect using SFTP.");
                return false;
            }
        } catch (Exception $e) {
            // error_log("Connection error with SFTP: " . $e->getMessage());
            return false;
        }
    } else {
        // Use PHP's native FTP functions if FTP is chosen
        $ftpConn = ftp_connect($ip, $port);
        if ($ftpConn && ftp_login($ftpConn, $username, $password)) {
            ftp_pasv($ftpConn, true); // Enable passive mode
            // error_log("Connected successfully using FTP.");
            return $ftpConn;
        } else {
           // error_log("Failed to connect using FTP.");
            return false;
        }
    }
}

function downloadFiles($protocol, $ip, $port, $username, $password, $remotePath, $localDir, $fileTypes = null) {
    $remote = initializeRemoteConnection($protocol, $ip, $port, $username, $password);
    if (!$remote) {
        if ($protocol === 'sftp') {
            error_log("SFTP failed; attempting FTP fallback...");
            $remote = initializeRemoteConnection('ftp', $ip, $port, $username, $password);
            if (!$remote) {
                error_log("Both SFTP and FTP connections failed.");
                return;
            }
        } 
        else {
            return;
        }
    }

    if (!file_exists($localDir)) {
        mkdir($localDir, 0775, true);
    }

    // Check if we're dealing with a directory or a single file
    if ($fileTypes === null) { // Single file download for CSV
        $localFilePath = rtrim($localDir, '/') . '/' . basename($remotePath);

        if ($protocol === 'sftp') {
            $downloaded = $remote->get($remotePath, $localFilePath);
        } else {
            // Ensure local file path is writable for FTP
            $localFile = fopen($localFilePath, 'w');  // Open file in write mode
            if (!$localFile) {
                error_log("Failed to open local file for writing: $localFilePath");
                return;
            }
            $downloaded = ftp_get($remote, $localFilePath, $remotePath, FTP_BINARY);
            fclose($localFile);  // Close file handle
        }

        if ($downloaded) {
            error_log("Downloaded: " . basename($remotePath));
        } else {
            error_log("Failed to download: " . basename($remotePath));
        }
    } 
    else { 
        // Directory download for images
        if ($protocol === 'sftp') {
            $files = $remote->nlist($remotePath);
        } else {
            $files = ftp_nlist($remote, $remotePath);
        }

        if (!$files || !is_array($files)) {
            error_log("No files found or failed to list files in remote directory: $remotePath");
            return;
        }

        // Download each file in the directory
        foreach ($files as $fileName) {
            $fileName = basename($fileName);  // Ensure only the filename is used
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($extension, $fileTypes)) {
                $remoteFilePath = $remotePath . '/' . $fileName;
                $localFilePath = rtrim($localDir, '/') . '/' . $fileName;

                if ($protocol === 'sftp') {
                    $downloaded = $remote->get($remoteFilePath, $localFilePath);
                } else {
                    $localFile = fopen($localFilePath, 'w');  // Open file in write mode
                    if (!$localFile) {
                        error_log("Failed to open local file for writing: $localFilePath");
                        continue;
                    }
                    $downloaded = ftp_get($remote, $localFilePath, $remoteFilePath, FTP_BINARY);
                    fclose($localFile);  // Close file handle
                }
            }
        }
    }

    if ($protocol === 'ftp') {
        ftp_close($remote); // Close FTP connection when done
    }
}


function process_download_images($sku) {
    // $protocol = get_option('BORN_WOO_FTP_IMAGES_PROTOCOL', '');
    $ip = get_option('BORN_WOO_FTP_IMAGES_IP', '');
    $port = get_option('BORN_WOO_FTP_IMAGES_PORT', '');
    $username = get_option('BORN_WOO_FTP_IMAGES_USERNAME', '');
    $password = get_option('BORN_WOO_FTP_IMAGES_PASSWORD', '');
    $remoteDirPath = get_option('BORN_WOO_FTP_IMAGES_PATH', '');
    $localDir = ABSPATH . "images/";

    // Ensure local directory exists
    if (!file_exists($localDir)) {
        mkdir($localDir, 0777, true);
    }

    $imageFiles = [
        "$sku.jpg",
        "$sku-1.jpg",
        "$sku-2.jpg",
        "$sku.JPG",
        "$sku-1.JPG",
        "$sku-2.JPG"
    ];

    // Connect to the FTP server
    $connection = ftp_connect($ip, $port, 90); // Increased timeout to 90 seconds
    if (!$connection) {
        // error_log("Failed to connect to FTP server $ip on port $port");
        return false;
    }

    // Login to the server
    if (!ftp_login($connection, $username, $password)) {
        // error_log("Failed to login to FTP server with username $username");
        ftp_close($connection);
        return false;
    }

    // Enable passive mode
    ftp_pasv($connection, true);

    foreach ($imageFiles as $file) {
        $remoteFile = $remoteDirPath . $file;
        $localFile = $localDir . $file;

        // Check if remote file exists and get its size and modified time
        $remoteSize = ftp_size($connection, $remoteFile);
        $remoteModified = ftp_mdtm($connection, $remoteFile);

        if ($remoteSize === -1 || $remoteModified === -1) {
           // error_log("Remote file $remoteFile does not exist or cannot fetch metadata.");
            continue;
        }

        // Check if the local file exists
        if (file_exists($localFile)) {
            $localSize = filesize($localFile);
            $localModified = filemtime($localFile);

            // Skip download if file size and modified date are the same
            if ($localSize == $remoteSize && $localModified == $remoteModified) {
               // error_log( "Skipped downloading $file. No changes detected.\n");
                continue;
            }
        }

        // Download the file
        $attempts = 2; // Retry up to 2 times
        while ($attempts > 0) {
            if (!ftp_get($connection, $localFile, $remoteFile, FTP_BINARY)) {
               // error_log("Failed to download $remoteFile. Retrying...");
                $attempts--;
               
            } else {
              //  error_log( "Successfully downloaded $remoteFile to $localFile\n");

                // Update local file's modified time to match the remote file
                touch($localFile, $remoteModified);
                break; // Exit retry loop on success
            }
        }

        if ($attempts === 0) {
           // error_log("Failed to download $remoteFile after multiple attempts.");
        }
    }

    // Close the FTP connection
    ftp_close($connection);
    return true;
}


function downloadProductImages( $BORN_WOO_FTP_CSV_ARTICULO ) {
    // error_log("downloadProductImages() - START OF EXECUTION");

    $BORN_WOO_FTP_CSV_ARTICULO = ABSPATH . "csv_files/CLEANED_ARTICULO_ING.csv";
    
    $csv = Reader::createFromPath($BORN_WOO_FTP_CSV_ARTICULO, 'r');
    $csv->setHeaderOffset(0);  // If your CSV has headers, set the header offset
    $csv->setDelimiter(';'); // Set the delimiter to semicolon
    $chunkSize = 10;
    $offset = 0;
    while (true) {
        $statement = (new Statement())->offset($offset)->limit($chunkSize);
        $rows = $statement->process($csv);
        if (empty($rows)) {
            unset($rows, $statement, $wpdb, $table_name);
            gc_collect_cycles();
            break;
        }
        foreach ($rows as $row) { 
            $sku = $row["Reference"];
            if ($sku) {
                process_download_images($sku);
            }
        }
        $offset += $chunkSize;
    }
    unset($rows, $statement);
    gc_collect_cycles();
}


function download_articulo_csv_cron_job() {
    $protocol = get_option('BORN_WOO_FTP_CSV_PROTOCOL', 'ftp');
    $ip = get_option('BORN_WOO_FTP_CSV_IP', '');
    $port = (int)get_option('BORN_WOO_FTP_CSV_PORT', 21);
    $username = get_option('BORN_WOO_FTP_CSV_USERNAME', '');
    $password = get_option('BORN_WOO_FTP_CSV_PASSWORD', '');
    $articuloCSVPath = get_option('BORN_WOO_FTP_CSV_ARTICULO', '');
    $localDir = ABSPATH . "csv_files/";

    $DownloadedArticuloCSV = ABSPATH."csv_files/".basename( $articuloCSVPath );
    downloadFiles($protocol, $ip, $port, $username, $password, $articuloCSVPath, $localDir);

    // I put this sleep 10seconds just to ensure that CSV is downloaded before editing.
    sleep(10);

    $inputFile = ABSPATH . 'csv_files/ARTICULO_ING.csv';
    $outputFile = ABSPATH . 'csv_files/CLEANED_ARTICULO_ING.csv';

    if (($handle = fopen($inputFile, 'r')) !== FALSE) {
        $headers = fgetcsv($handle, 0, ';'); // Read header row with ';' as delimiter

        if ($headers === FALSE) {
            fclose($handle);
            return;
        }

        // Columns to remove
        $columnsToRemove = ['CN', 'BR', 'CA', 'DD'];
        $indexesToRemove = [];

        foreach ($headers as $index => $columnName) {
            if (in_array(trim($columnName), $columnsToRemove, true)) {
                $indexesToRemove[] = $index;
            }
        }

        // Handle duplicate column names
        $columnCounts = [];
        $newHeaders = [];

        foreach ($headers as $index => $columnName) {
            if (!in_array($index, $indexesToRemove)) {
                $trimmedName = trim($columnName);
                if (isset($columnCounts[$trimmedName])) {
                    $columnCounts[$trimmedName]++;
                    $newHeaders[] = $trimmedName . $columnCounts[$trimmedName];
                } else {
                    $columnCounts[$trimmedName] = 1;
                    $newHeaders[] = $trimmedName;
                }
            }
        }

        // Process remaining rows and ensure correct alignment
        $data = [];
        while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
            $filteredRow = array_diff_key($row, array_flip($indexesToRemove));
            if (count($filteredRow) === count($newHeaders)) {
                $data[] = $filteredRow;
            }
        }
        fclose($handle);

        // Save cleaned data with headers
        $outputHandle = fopen($outputFile, 'w');

        // Write headers manually to avoid extra quotes
        fwrite($outputHandle, implode(';', $newHeaders) . PHP_EOL);

        // Write each row manually to avoid extra quotes
        foreach ($data as $row) {
            fwrite($outputHandle, implode(';', $row) . PHP_EOL);
        }

        fclose($outputHandle);
    }
}

function download_stocks_csv_cron_job() {
    $protocol = get_option('BORN_WOO_FTP_CSV_PROTOCOL', 'ftp');
    $ip = get_option('BORN_WOO_FTP_CSV_IP', '');
    $port = (int)get_option('BORN_WOO_FTP_CSV_PORT', 21);
    $username = get_option('BORN_WOO_FTP_CSV_USERNAME', '');
    $password = get_option('BORN_WOO_FTP_CSV_PASSWORD', '');
    $stocksCSVPath = get_option('BORN_WOO_FTP_CSV_STOCKS', '');
    $localDir = ABSPATH . "csv_files/";

    downloadFiles($protocol, $ip, $port, $username, $password, $stocksCSVPath, $localDir);
}