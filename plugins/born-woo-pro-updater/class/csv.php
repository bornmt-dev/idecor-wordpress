<?php

use phpseclib3\Net\SFTP;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;

class iDecorCSV {

    public function initializeRemoteConnection($protocol, $ip, $port, $username, $password) {
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
    
    public function downloadFiles($protocol, $ip, $port, $username, $password, $remotePath, $localDir, $fileTypes = null) {
        $remote = $this->initializeRemoteConnection($protocol, $ip, $port, $username, $password);
        if (!$remote) {
            if ($protocol === 'sftp') {
                error_log("SFTP failed; attempting FTP fallback...");
                $remote = $this->initializeRemoteConnection('ftp', $ip, $port, $username, $password);
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


    public function downloadCSV( ) {  
        $protocol = get_option('BORN_WOO_FTP_CSV_PROTOCOL', 'ftp');
        $ip = get_option('BORN_WOO_FTP_CSV_IP', '2.139.176.160');
        $port = (int)get_option('BORN_WOO_FTP_CSV_PORT', 21);
        $username = get_option('BORN_WOO_FTP_CSV_USERNAME', 'jarom_stocks');
        $password = get_option('BORN_WOO_FTP_CSV_PASSWORD', 'JtE6tn23lj6f8*');
        $articuloCSVPath = get_option('BORN_WOO_FTP_CSV_ARTICULO', '/STOCKS/ARTICULO_ING.csv');
        $localDir = ABSPATH . "csv_files/";

        $DownloadedArticuloCSV = ABSPATH."csv_files/".basename( $articuloCSVPath );
        $this->downloadFiles($protocol, $ip, $port, $username, $password, $articuloCSVPath, $localDir);

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
}