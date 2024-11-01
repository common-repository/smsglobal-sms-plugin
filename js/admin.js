function smsglobal_change_nav(e, t) {
    return jQuery(".smsglobal_nav_tabs a").removeClass("nav-tab-active"), jQuery(e).addClass("nav-tab-active"), jQuery(".smsglobal_nav_box").removeClass("smsglobal_active"), jQuery("." + t).addClass("smsglobal_active"), !1
}

function disabledMessage() {
    for (var e = jQuery('.smsglobal_box input[type="checkbox"]').length, t = 0; e > t; t++) jQuery('.smsglobal_box input[type="checkbox"]').eq(t).is(":checked") === !1 ? jQuery(jQuery(this).parent().attr("data-href")).find("textarea").attr("readonly", !0) : jQuery(jQuery(this).parent().attr("data-href")).find("textarea").removeAttr("readonly")
}

function verifyUser(e) {
    var t = jQuery('input[data-id="smsglobal_api_key"]').val(), r = jQuery('input[data-id="smsglobal_secret"]').val(),
        s = "admin.php", a = jQuery(e).text();
    jQuery(e).text("Loading..."), "" == t || "" == r ? (jQuery("#verify_status").html("<strong>Please Enter API &amp; secret keys.</strong>").fadeOut(3e3, function () {
            jQuery("#verify_status").html(""), jQuery("#verify_status").removeAttr("style")
        }), jQuery(e).text(a)) :
        jQuery.ajax({
            url: s + '?option=login',
            type: "POST",
            data: {api_key: t, secret: r},
            success: function(e) {
                window.location.replace(window.location.href.substring(0, window.location.href.indexOf('&')));
            },
            error: function (e) {
                window.location.replace(window.location.href.substring(0, window.location.href.indexOf('&')));
            }
        });
}


function logout() {
    var e = "admin.php";
    jQuery.ajax({
        url: e,
        type: "GET",
        data: "option=logout",
        crossDomain: !0,
        dataType: "json",
        contentType: "application/json; charset=utf-8",
        success: function (e) {
            window.location.reload()
        },
        error: function (e) {
            console.log(e)
        }
    })
}

function selecttemplate(e, t) {
    return jQuery(t).val(e.value), jQuery(t).trigger("change"), !1
}

function create_group(e) {
    var t = jQuery('input[data-id="smsglobal_name"]').val(), r = jQuery('input[data-id="smsglobal_password"]').val(),
        s = "admin.php";
    return jQuery(e).text("Please Wait..."), jQuery.ajax({
        url: s,
        type: "GET",
        data: "option=smsalert-woocommerce-creategroup&user=" + encodeURIComponent(t) + "&pwd=" + encodeURIComponent(r),
        crossDomain: !0,
        dataType: "json",
        contentType: "application/json; charset=utf-8",
        success: function (t) {
            if ("object" == typeof t) var r = t; else var r = jQuery.parseJSON(t);
            "success" == r.status && (jQuery('select[id="group_auto_sync"]').removeAttr("disabled"), jQuery('select[id="group_auto_sync"]').html(""), jQuery.each(r.description, function (t, r) {
                jQuery('select[id="group_auto_sync"]').append(jQuery("<option></option>").attr("value", r.Group.id).text(r.Group.name)), jQuery(e).remove()
            }))
        },
        error: function (e) {
            console.log(e)
        }
    }), !1
}

jQuery(window).load(function (e) {
    var t = window.location.hash, r = window.location.hash.substr(1).replace('/', '').split('?')[0];
    "" != r && "" != t && (jQuery(".smsglobal_nav_tabs li").removeClass("smsglobal_active"), jQuery(".smsglobal_nav_box").removeClass("smsglobal_active"), jQuery('a[href="' + t + '"]').parent().addClass("smsglobal_active"), jQuery(".smsglobal_box ." + r).addClass("smsglobal_active")), disabledMessage()
}), jQuery('.smsglobal_box input[type="checkbox"]').click(function () {
    jQuery(this).is(":checked") === !1 ? 0 == jQuery(this).parent().find('input[type="checkbox"]:checked').length && jQuery(this).parent().parent().find("textarea").attr("readonly", !0) : jQuery(this).parent().parent().find("textarea").removeAttr("readonly")
}), jQuery("#smsglobal_sms_order_message").on("change keyup input", function () {
    jQuery("#smsglobal_sms_order_message_char_count").text(jQuery(this).val().length), jQuery(this).val().length > 968 ? jQuery("#smsglobal_sms_order_message_char_count").css("color", "red") : jQuery("#smsglobal_sms_order_message_char_count").css("color", "green")
}), jQuery("a#smsglobal_sms_order_send_message").click(function (e) {
    var t = jQuery("div#smsglobal_send_sms_meta_box"), r = jQuery("textarea#smsglobal_sms_order_message");
    if ($orderid = jQuery("input#smsglobal_order_id"), "" == r.val()) return alert("Please Enter Your Message."), !1;
    if (t.is(".processing")) return !1;
    t.addClass("processing").block({
        sms_body: null,
        overlayCSS: {background: "#fff", backgroundSize: "100px 400px", opacity: .6}
    });
    var s = {action: "smsglobal_sms_send_order_sms", sms_body: r.val(), order_id: $orderid.val()};
    return jQuery.ajax({
        type: "POST", url: ajaxurl, data: s, success: function (e) {
            if (t.removeClass("processing").unblock(), e) {
                var s = JSON.parse(e), a = "error" == s.status ? s.description.desc : "Sent Successfully.";
                t.addClass("smsstatus").block({
                    sms_body: null,
                    timeout: 2e3,
                    message: a,
                    css: {background: "#fff", padding: "10px"}
                }), r.val("")
            }
        }, dataType: "html"
    }), !1
});