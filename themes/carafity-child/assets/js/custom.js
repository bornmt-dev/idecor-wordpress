jQuery(document).ready(function ($) {
    // Check if the "Shop All" link already exists
    if ($('.custom-cart-content a.custom-cart-shopbtn').length === 0) {
        // Initial check and adjustment
        handleScreenSize();

        // Event listener for window resize
        $(window).on('resize', handleScreenSize);

        function handleScreenSize() {
            // Remove any existing "Shop All" links
            $('.custom-cart-content a.custom-cart-shopbtn').remove();

            if ($(window).width() <= 768) {
                $('.custom-cart-content .woocommerce').append('<a class="custom-cart-shopbtn" href="/shop">Shop All <span class="elementor-button-icon"><i aria-hidden="true" class="carafity-icon- carafity-icon-long-arrow-right"></i> </span></a>');
            } else {
                $('.custom-cart-content .cross-sells h2').append('<a class="custom-cart-shopbtn" href="/shop">Shop All <span class="elementor-button-icon"><i aria-hidden="true" class="carafity-icon- carafity-icon-long-arrow-right"></i> </span></a>');
            }
        }
    }

});

jQuery(document).ready(function ($) {
    // Check if the "Shop All" link already exists
    if ($('.single.single-product .related.products a.custom-cart-shopbtn').length === 0) {
        // Initial check and adjustment
        handleScreenSize();

        // Event listener for window resize
        $(window).on('resize', handleScreenSize);

        function handleScreenSize() {
            // Remove any existing "Shop All" links
            $('.single.single-product .related.products a.custom-cart-shopbtn').remove();

            if ($(window).width() <= 768) {
                $('.single.single-product .related.products').append('<a class="custom-cart-shopbtn" href="/shop">Shop All <span class="elementor-button-icon"><i aria-hidden="true" class="carafity-icon- carafity-icon-long-arrow-right"></i> </span></a>');
            } else {
                $('.single.single-product .related.products h2').append('<a class="custom-cart-shopbtn" href="/shop">Shop All <span class="elementor-button-icon"><i aria-hidden="true" class="carafity-icon- carafity-icon-long-arrow-right"></i> </span></a>');
            }
        }
    }

});

jQuery(document).ready(function ($) {
    $('.single.single-product div.product .summary.entry-summary .woosw-btn').text('Browse wishlist');
});


jQuery(document).ready(function ($) {
    // Wait for DOM to stabilize (if necessary)
    $(document).on('DOMContentLoaded', function () {
        // Check if Swiper library is loaded
        if (typeof Swiper !== 'undefined') {
            // Get the Swiper instance
            var swiper = $('.custom-cart-content .cross-sells .products-carousel .swiper');

            // Check if Swiper instance exists
            if (swiper.length > 0) {
                // Modify Swiper options
                swiper.swiper('setOption', {
                    slidesPerView: 4,
                    // Other Swiper options as needed
                });

                // Re-initialize Swiper if necessary
                swiper.swiper('update');
            } else {
                console.error('Swiper instance not found.');
            }
        } else {
            console.error('Swiper library not loaded.');
        }
    });
});

jQuery(document).ready(function ($) {
    $('.elementor-post-info__terms-list-item a').each(function () {
        $(this).removeAttr('href');
    });

    $('.elementor-icon-list-item a').each(function () {
        $(this).removeAttr('href');
    });

    $('.woocommerce-breadcrumb ').each(function () {
        $(this).removeAttr('href');
    });

    $('.single-product .woocommerce-breadcrumb a:first').text('Home');

    var text = $('.woocommerce-Price-amount bdi').text();
    text = text.replace('&nbsp;', '');
    $('woocommerce-Price-amount bdi').text(text);

    $('.woocommerce-Price-amount bdi').each(function () {
        var text = $(this).text();
        text = text.replace(/\u00A0/g, '');
        $(this).text(text);
    });


    // Inner Blog
    $('.single-post .gamma.comment-reply-title').text('Leave a Comment');
    
    $('nav.post-navigation .nav-previous .reader-text').text('Previous Post');

    $('nav.post-navigation .nav-next .reader-text').text('Next Post');

    $('.single-post .comment-form-author #author').attr('placeholder', 'Name *');

    $('.single-post .comments-title').text('Comments');

    $('.single-product .resp-accordion.tab-reviews span').html(function (_, html) {
        return html.replace('(', '&nbsp;(');
    });

    // Shop
    $('.woocommerce-Price-amount.amount').each(function () {
        var priceText = $(this).text();

        if (priceText.endsWith('.00')) {
            $(this).text(priceText.slice(0, -3));
        }
    });

    $('label[for="pa_color"]').text('colour');

    setTimeout(function() {
        $('.archive .woosw-popup-content-bot-inner a.woosw-page').attr('href', "/wishlist");
    }, 3000);

    $('.archive .woosw-popup-content-bot-inner a.woosw-page').on('click', function() {
        window.location.href = '/wishlist';
    });

    $('.wpcf7-textarea').attr('rows', function (i, oldRows) {
        return parseInt(oldRows) + 1;
    });
});

jQuery(document).ready(function ($) { 
    if ($('.page-numbers').length > 0) {
        $('ul.page-numbers .page-numbers').each(function () {
            // Get all classes of the current element
            const allClasses = $(this).attr('class');
            
            // Get the text content of the element and trim whitespace
            const textContent = $(this).text().trim();
            
            // Check the length of the text content (digits/characters)
            const textLength = textContent.length;
            
            // Adjust font size based on the length of the text content
             if (textLength === 3) {
                $(this).css("font-size", "17px"); // No !important
            } 
            else if (textLength === 4) {
                $(this).css("font-size", "16px"); // No !important
            } 
            else if (textLength === 5) {
                $(this).css("font-size", "14px"); // No !important
            }
        });
    } 
});