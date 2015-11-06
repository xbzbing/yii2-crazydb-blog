$(function () {
    $('.modal').on('show.bs.modal', function () {
        var model = $(this);
        var modal_dialog = model.find('.modal-dialog');
        model.css('display', 'block');
        modal_dialog.css({'margin-top': Math.max(0, ($(window).height() - modal_dialog.height()) / 2)});
        var now_date = new Date();
        $('#issue-create_time').val(now_date.getFullYear() + '-' + now_date.getMonth() + '-' + now_date.getDate() + ' ' + now_date.getHours() + ':' +  now_date.getMinutes() + ':' + now_date.getSeconds())
    }).on('hidden.bs.modal', function(){
        $(this).find('form')[0].reset();
    });
    Messenger.options = {
        extraClasses: 'messenger-fixed messenger-on-bottom messenger-on-right',
        theme: 'future'
    }
});

(function ($, AdminLTE) {

    "use strict";

    /**
     * List of all the available skins
     *
     * @type Array
     */
    var my_skins = [
        "skin-blue",
        "skin-black",
        "skin-red",
        "skin-yellow",
        "skin-purple",
        "skin-green",
        "skin-blue-light",
        "skin-black-light",
        "skin-red-light",
        "skin-yellow-light",
        "skin-purple-light",
        "skin-green-light"
    ];


    setup();

    function change_skin(cls) {
        $.each(my_skins, function (i) {
            $("body").removeClass(my_skins[i]);
        });

        $("body").addClass(cls);
        store('skin', cls);
        return false;
    }

    function store(name, val) {
        if (typeof (Storage) !== "undefined") {
            localStorage.setItem(name, val);
        } else {
            window.alert('Please use a modern browser to properly view this template!');
        }
    }

    function get(name) {
        if (typeof (Storage) !== "undefined") {
            return localStorage.getItem(name);
        } else {
            window.alert('Please use a modern browser to properly view this template!');
        }
    }

    function setup() {
        var tmp = get('skin');
        if (tmp && $.inArray(tmp, my_skins))
            change_skin(tmp);

        $("[data-skin]").on('click', function (e) {
            e.preventDefault();
            change_skin($(this).data('skin'));
        });

        $("[data-sidebarskin='toggle']").on('click', function () {
            var sidebar = $(".control-sidebar");
            if (sidebar.hasClass("control-sidebar-dark")) {
                sidebar.removeClass("control-sidebar-dark");
                sidebar.addClass("control-sidebar-light");
            } else {
                sidebar.removeClass("control-sidebar-light");
                sidebar.addClass("control-sidebar-dark");
            }
        });
    }
})(jQuery, $.AdminLTE);
