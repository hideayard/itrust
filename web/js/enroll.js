$(document).ready(function (e) {

    $('.section-toggler').click(function (e) {
        e.preventDefault();

        $(this).closest('.course-section').toggleClass('open');
        $(this).find('span').toggleClass('fa-chevron-down');
        $(this).find('span').toggleClass('fa-chevron-up');
    });

    $('#course-nav-toggler, #course-nav-toggler2').click(function (e) {
        e.preventDefault();

        $('#course-nav').toggle();
        $('#course-content').toggleClass('col-lg-9').toggleClass('col-md-9').toggleClass('col-sm-9');
        $('#course-content').toggleClass('col-lg-12').toggleClass('col-md-12').toggleClass('col-sm-12');
        $('#course-nav-toggler2').toggleClass('d-none');
    });

    $('.tab-navigation .tab').click(function (e) {
        $('.tab-navigation .tab').removeClass('active');
        $(this).addClass("active");

        $('.tab-contents .tab-content').removeClass('active');

        const target = $(this).data('target');
        console.log(target);
        $('.tab-contents #' + target).addClass('active');
    });

});