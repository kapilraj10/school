(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        return;
    }

    $(function () {
        var $responsiveMenu = $('#mg-responsive-navigation');

        if ($responsiveMenu.length && $.fn.dlmenu) {
            $responsiveMenu.each(function () {
                var $menu = $(this);

                $menu.find('.dl-submenu').each(function () {
                    var $submenu = $(this);
                    var $triggerLink = $submenu.siblings('a');
                    var href = $triggerLink.attr('href');

                    if (href && href !== '#') {
                        var $linkItem = $('<li class="menu-item mg-parent-menu"></li>');
                        $linkItem.append($triggerLink.clone());
                        $submenu.prepend($linkItem);
                    }
                });

                $menu.dlmenu();
            });
        }

        var $desktopMenuItems = $('.navigation > ul > li');
        $desktopMenuItems.has('ul.children').addClass('menu-item-has-children');

        $('.navigation > ul > li.menu-item-has-children > a').on('click', function (event) {
            if (window.matchMedia('(max-width: 991px)').matches) {
                event.preventDefault();
                $(this).siblings('ul.children').stop(true, true).slideToggle(200);
            }
        });

        if ($.fn.slick && $('.slider').length && !$('.slider').hasClass('slick-initialized')) {
            $('.slider').slick({
                fade: true,
                arrows: false,
                dots: true,
                autoplay: true,
                autoplaySpeed: 2000
            });
        }
    });
})(window.jQuery);
