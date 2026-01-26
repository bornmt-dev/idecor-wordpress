<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>
<div class="product_meta">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'carafity' ) . ' ', '</span>' ); ?>

	<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'carafity' ) . ' ', '</span>' ); ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>
</div>
<!--end product_meta-->


<div class="product-share"><span class="product-inner-share">Share:</span>
    <a href="#" onclick="shareOnFacebook('<?php echo get_permalink(); ?>'); return false;" target="_blank">
		<img src="/wp-content/uploads/2024/08/Facebook-logo.svg">
    </a>
	<a href="#" onclick="copyAndRedirect('<?php echo get_permalink(); ?>', 'https://www.instagram.com'); return false;">
		<img src="/wp-content/uploads/2024/08/Instagram-logo.svg">
    </a>
    
</div>

<script>
function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
}

function showMessage(message) {
    var msgDiv = document.createElement("div");
    msgDiv.style.position = "fixed";
    msgDiv.style.bottom = "10px";
    msgDiv.style.left = "50%";
    msgDiv.style.transform = "translateX(-50%)";
    msgDiv.style.backgroundColor = "#333";
    msgDiv.style.color = "#fff";
    msgDiv.style.padding = "10px 20px";
    msgDiv.style.borderRadius = "5px";
    msgDiv.style.zIndex = "1000";
    msgDiv.innerText = message;

    document.body.appendChild(msgDiv);

    setTimeout(function() {
        document.body.removeChild(msgDiv);
    }, 3000);
}

function shareOnFacebook(url) {
    var shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    window.open(shareUrl, '_blank');
    showMessage('Shared on Facebook');
}

function shareOnLinkedIn(url, title) {
    var shareUrl = 'https://www.linkedin.com/shareArticle?mini=true&url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title);
    window.open(shareUrl, '_blank');
    showMessage('Shared on LinkedIn');
}

function copyAndRedirect(text, url) {
    copyToClipboard(text);
    showMessage('Link copied to clipboard. Redirecting to Instagram...');
    setTimeout(function() {
        window.open(url, '_blank');
    }, 1000); // Small delay to ensure the message is shown
}
</script>