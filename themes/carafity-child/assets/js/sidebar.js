document.addEventListener("DOMContentLoaded", function () {
    const menuParents = document.querySelectorAll(".widget_nav_menu .menu-item-has-children > a");

    menuParents.forEach(parent => {
        parent.addEventListener("click", function (e) {
            e.preventDefault(); // Prevent navigation

            const li = this.parentElement;
            const submenu = this.nextElementSibling;

            // Close all other open siblings (accordion behavior)
            const siblings = li.parentElement.querySelectorAll(".menu-item-has-children.open");
            siblings.forEach(sib => {
                if (sib !== li) {
                    sib.classList.remove("open");
                    const sibSubmenu = sib.querySelector(".sub-menu, .children");
                    if (sibSubmenu) sibSubmenu.style.display = "none";
                }
            });

            // Toggle current submenu
            if (submenu.style.display === "block") {
                submenu.style.display = "none";
                li.classList.remove("open");
            } else {
                submenu.style.display = "block";
                li.classList.add("open");
            }
        });
    });
});
