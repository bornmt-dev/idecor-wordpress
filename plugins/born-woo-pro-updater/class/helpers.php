<?php
class iDecorHelpers {

    public function formatProductName( $original_name ) { 

        // Sanitize and clean the name
        $clean_name = sanitize_text_field( $original_name );
        $clean_name = preg_replace('/\d.*$/', '', $clean_name); // Remove digits and everything after
        $clean_name = trim( $clean_name );

        // Fallback if the cleaning removed everything
        if ( $clean_name === '' ) {
            $clean_name = sanitize_text_field( $original_name );
        }

        return $clean_name; 
    }

    public function calculatePrice($raw_price) {
        $clean_raw_price = floatval(str_replace(',', '.', $raw_price));
        // Check for empty or zero value
        if (empty($clean_raw_price) || $clean_raw_price == 0) {
            $price = null; 
            return $price;
        }
        else {
            if ($clean_raw_price >= 0.01 && $clean_raw_price <= 94.99) {
                $price = $clean_raw_price * 3;
            }  
            elseif ($clean_raw_price >= 95.00) {
                $price = $clean_raw_price * 2.5;
            }
            else {
                $price = 0;
                return $price;
            }
            if ( $price > 0 ) {
                // Limit the result to 2 decimal places
                $price = round($price, 2);
                // Handle prices less than 100 (single and two-digit numbers)
                if ($price < 100) {
                    // Round to nearest odd number
                    $rounded = ceil($price);
                    if ($rounded % 2 == 0) {
                        // If the rounded number is even, add 1 to make it odd
                        $rounded++;
                    }
                    // Return the price with .99 as required
                    $return_price = number_format($rounded, 0) . '.99';
                }
                // Handle prices with three digits (100 or more)
                else {
                    // Round to the nearest whole number
                    $rounded = round($price);
                    // Make sure it's an odd number
                    if ($rounded % 2 == 0) {
                        $rounded++;
                    }
                    // Return the price as a whole number without decimals
                    $return_price = number_format($rounded, 0);
                }
                // error_log("return_price: ". $return_price);
                return $return_price;
            }
        }
    }

    public function isInboundStocks ( $available_stock, $upcoming_stocks, $inbound ) {
        if ( (int)$upcoming_stocks > 0 && !empty($inbound) && $available_stock != 0) {
            return $upcoming_stocks;
        }
        else {
            return false;
        }
    }

}