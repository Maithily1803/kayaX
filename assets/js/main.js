$(function () {

    window.toast = function (msg, type) {
        type = type || 'info';
        var $t = $('<div class="toast ' + type + '">' + msg + '</div>');
        $('#toast-container').append($t);
        setTimeout(function () { $t.fadeOut(400, function () { $t.remove(); }); }, 3200);
    };

    window.ajax = function (url, data, method) {
        method = method || 'GET';
        return $.ajax({
            url:         url,
            type:        method,
            data:        data || {},
            dataType:    'json',
            headers:     { 'X-Requested-With': 'XMLHttpRequest' },
        });
    };

    window.openPanel = function (id) {
        $('#' + id).addClass('open');
        $('#overlay').addClass('visible');
    };

    window.closePanel = function (id) {
        $('#' + id).removeClass('open');
        $('#overlay').removeClass('visible');
    };

    $('#overlay').on('click', function () {
        $('.insight-panel.open').each(function () {
            $(this).removeClass('open');
        });
        $(this).removeClass('visible');
    });

    $('[data-panel]').on('click', function () {
        openPanel($(this).data('panel'));
    });

    $('[data-close-panel]').on('click', function () {
        closePanel($(this).data('close-panel'));
    });

    var path = window.location.pathname.split('/').pop();
    $('.nav-link').each(function () {
        var href = $(this).attr('href') || '';
        if (href.indexOf(path) !== -1) $(this).addClass('active');
    });

    $('[data-logout]').on('click', function () {
        ajax('../api/auth.php', { action: 'logout' }, 'POST').done(function (r) {
            if (r.success) window.location.href = r.redirect;
        });
    });

    $('body').append('<div id="toast-container" class="toast-container"></div>');
    $('body').append('<div id="overlay" class="overlay"></div>');
});