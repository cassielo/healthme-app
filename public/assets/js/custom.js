// Login and register switch
function show(obj, log, res) {
    var o = document.getElementById(log);
    if (o.style.display === 'none') {
        o.style.display = '';
    } else {
        o.style.display = 'none';
    }
    var o2 = document.getElementById(res);
    if (o2.style.display === 'none') {
        o2.style.display = '';
    } else {
        o2.style.display = 'none';
    }
}

// Profile tabs
$(document).ready(function () {
    $('.tab').not(':first').hide();
    $('.tab:first').show();
    $('#tabs li a:first').addClass('active');
    $('ul#tabs li a').click(function () {
        $('ul#tabs li a').removeClass('active');
        $('.tab:visible').hide();
        var index = $('ul#tabs li a').index(this);
        $('.tab:eq(' + index + ')').fadeIn('slow');
        $(this).addClass('active');
        return false;
    });
});

// Navigation bar
$(document).ready(function () {
    'use strict';

    // Sticky once scrolled 50px
    (function () {
        var docElem = document.documentElement,
            didScroll = false,
            stickyNav = 50;

        function init() {
            window.addEventListener('scroll', function () {
                if (!didScroll) {
                    didScroll = true;
                    setTimeout(scrollPage, 50);
                }
            }, false);
        }

        function scrollPage() {
            var sy = scrollY();
            if (sy >= stickyNav) {
                $('.nav-container').addClass('sticky');
            } else {
                $('.nav-container').removeClass('sticky');
            }
            didScroll = false;
        }

        function scrollY() {
            return window.pageYOffset || docElem.scrollTop;
        }

        init();
    })();
});

// Full-screen menu
$(document).ready(function () {
    'use strict';
    $('.menu-container').each(function (index) {
        $(this).find('.circle').attr('menu-link', index);
        $(this).find('.list-menu').clone().appendTo('body').attr('menu-link', index);
    });
    $('.menu-container .circle').click(function () {
        var linkedVideo = $('section').closest('body').find('.list-menu[menu-link="' + $(this).attr('menu-link') + '"]');
        linkedVideo.toggleClass('reveal-modal');
    });
    $('section').closest('body').find('.close-iframe').click(function () {
        $(this).closest('.list-menu').toggleClass('reveal-modal');
    });
});
