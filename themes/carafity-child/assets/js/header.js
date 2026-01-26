jQuery(document).ready(function ($) {
  let headerHeight = 0;
  if ($('body').hasClass('page-id-181') || $('body').hasClass('page-id-187')) {
    headerHeight = 500;
  }

  // --- HEADER TOGGLE ---
  function toggleMegaMenu() {
    const scrollTop = $(window).scrollTop();
    $('.sub-menu.mega-menu').show();

    if (scrollTop > headerHeight) {
      $("#masthead").removeClass("sticked").addClass("scrolled");
    } else {
      $("#masthead").addClass("sticked").removeClass("scrolled");
    }
  }
  toggleMegaMenu();
  $(window).scroll(toggleMegaMenu);

  // --- COPYRIGHT YEAR ---
  $(".idecor-copyright-year").html(new Date().getFullYear());


    // Function to bind scroll-to-top on category links
    function bindCategoryScroll() {
        $(".bwp-filter-category a").off("click.scrollTop").on("click.scrollTop", function() {
            $("html, body").animate({ scrollTop: 0 }, "slow");
        });
    }

    // Initial binding
    bindCategoryScroll();

    // Re-bind after every AJAX request
    $(document).ajaxComplete(function() {
        bindCategoryScroll();
    });


});
