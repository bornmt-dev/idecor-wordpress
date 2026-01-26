jQuery(document).ready(function () {
  var elem = document.querySelector(".grid");
  var msnry = new Masonry(elem, {
    // options
    itemSelector: ".grid-item",
    gutter: ".gutter-sizer",
    percentPosition: true,
    horizontalOrder: true,
  });
});
