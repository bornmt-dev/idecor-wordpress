<?php
/**
 * Proceed to checkout button
 *
 * Contains the markup for the proceed to checkout button on the cart.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/proceed-to-checkout-button.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<!-- <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
	<?php esc_html_e( 'Proceed to checkout', 'woocommerce' ); ?>
</a> -->

<a class="checkout-button button alt wc-forward download-cart-as-pdf" onclick="iDecorPrintReceipt()" type="button">Download PDF</a>

<?php
function get_cart_products() {
    // Ensure WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        return null;
    }

    // Initialize an array to store cart products
    $cart_products = [];

    // Get WooCommerce cart items
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        // Add product data to array
        $cart_products[] = [
            'product_id'   => $product->get_id(),
            'name'          => $product->get_name(),
            'sku'           => $product->get_sku(),
            'price'         => $product->get_price(),
            'quantity'      => $cart_item['quantity'],
            'subtotal'      => WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ),
            'image'         => wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' )[0],
        ];
    }

    return $cart_products;
}

// Generate hidden cart data for printing
$cart_products = get_cart_products();
if ( ! empty( $cart_products ) ) {



    echo '<div id="printArea" style="display: none;">';
    echo '<table border="1" cellpadding="10" cellspacing="0" width="100%">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>SKU</th>';
    echo '<th>Price</th>';
    echo '<th>Quantity</th>';
    echo '<th>Subtotal</th>';
    echo '<th>Image</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ( $cart_products as $product ) {
      $product_id = $product['product_id'];
      $html_inbound_date = "";
      $stock_inbound_date = get_post_meta( $product_id, 'stock_inbound_date', true );
      if ( ! empty( $stock_inbound_date ) ) {
          $html_inbound_date = '<span style="font-size: 13px;" class="stock-inbound-date">Expected: ' 
               . esc_html( format_stock_inbound_date( $stock_inbound_date ) ) 
               . '</span><br/>';
      }

        echo '<tr>';
        echo '<td>' .$html_inbound_date. $product['name'] . '</td>';
        echo '<td>' . $product['sku'] . '</td>';
        echo '<td>' . wc_price( $product['price'] ) . '</td>';
        echo '<td>' . $product['quantity'] . '</td>';
        echo '<td>' . $product['subtotal'] . '</td>';
        echo '<td><img src="' . $product['image'] . '" alt="' . $product['name'] . '" width="50"></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    
    // Add tfoot for totals
    echo '<tfoot>';
    echo '<tr>';
    echo '<th colspan="4">Total Amount</th>';
    echo '<td colspan="2">' . WC()->cart->get_total() . '</td>';
    echo '</tr>';
    echo '</tfoot>';

    echo '</table>';

    // Display coupon codes
    echo '<br><strong>Coupons:</strong><br>';
    $coupons = WC()->cart->get_coupons();
    if ( ! empty( $coupons ) ) {
        foreach ( $coupons as $code => $coupon ) {
            echo 'Code: ' . esc_html( $code ) . '<br>';
        }
    } else {
        echo 'No coupons applied.<br>';
    }

    echo '</div>';
} else {
    echo 'Your cart is currently empty.';
}
?>

<script>
function iDecorPrintReceipt() {
  // Get the hidden print area content
  var printContents = document.getElementById("printArea").innerHTML;
  var logoPath = '/wp-content/uploads/2024/10/logo.png'; // Path to the logo

  // Open a new window
  var newWindow = window.open("", "_blank");

  // Write the content into the new window
  newWindow.document.write(`
    <html>
      <head>
        <title>iDecor - Cart Summary</title>
        <style>
          body {
            font-family: Arial, sans-serif;
            padding: 20px;
            color: black;
            text-align: center;
          }
          #header {
            background-color: #6C4C2BE5;
            padding: 20px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
          }
          #header h2 {
            font-size: 24px;
            margin: 0;
          }
          #logo {
            width: 150px;
            margin-bottom: 10px;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #ffffff;
            color: black;
          }
          table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
          }
          th {
            background-color: #f4f4f4;
            text-align: left;
          }
          tr:nth-child(even) {
            background-color: #f9f9f9;
          }
          tr:hover {
            background-color: #f1f1f1;
          }
          th, td {
            padding: 12px;
            text-align: left;
          }
          img {
            border-radius: 5px;
            max-width: 50px;
          }
          tfoot {
            font-weight: bold;
            background-color: #f4f4f4;
          }
        </style>
      </head>
      <body>
        <div id="header">
          <img id="logo" src="${logoPath}" alt="Company Logo">
          <h2>iDecor Cart Summary</h2>
        </div>
        ${printContents}
      </body>
    </html>
  `);

  // Ensure the content is fully loaded before printing
  newWindow.document.close();
  newWindow.focus();

  // Trigger the print dialog
  setTimeout(function() {
    newWindow.print();
    newWindow.close();
  }, 500); // Adjust the timeout as necessary
}
</script>
