$(function () {
    //表情显示与留言添加
    $("#more_smilie").click(function () {
        $(this).remove();
        $("img[data-type=comment-smilie]").each(function () {
            $(this).attr("src", smile_path + $(this).attr("data-smilie") + ".gif");
        });
        $(".smilie-img").animate({width: "100%"});
    });
    $("img[data-type=comment-smilie]").click(function () {
        var myField;
        var tag = $(this).attr("data-smilie");
        tag = ' :' + tag + ': ';
        if (document.getElementById('comment-content') && document.getElementById('comment-content').type == 'textarea') {
            myField = document.getElementById('comment-content');
        } else {
            return false;
        }
        if (document.selection) {
            myField.focus();
            var sel = document.selection.createRange();
            sel.text = tag;
            myField.focus();
        }
        else if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            var cursorPos = endPos;
            myField.value = myField.value.substring(0, startPos)
                + tag
                + myField.value.substring(endPos, myField.value.length);
            cursorPos += tag.length;
            myField.focus();
            myField.selectionStart = cursorPos;
            myField.selectionEnd = cursorPos;
        }
        else {
            myField.value += tag;
            myField.focus();
        }
    });
    //发表留言按钮
    $('#sendComment').click(function () {
        if (checkRequire()) {
            $("#comment-error").slideDown(300).delay(4000).slideUp(300);
            return false;
        }
        onTransfer();
        var form = $("#add-comment");
        $.ajax({
            'type': 'POST',
            'url': form.attr('action'),
            'cache': false,
            'dataType': 'json',
            'data': form.serialize(),
            'success': function (data, status) {
                if (data.status == "fail") {
                    $("#comment-error").append(data.info).slideDown(300).delay(4000).slideUp(300);
                } else if (data.status == "success") {
                    if (data.display == 1) {
                        if (data.reply_to > 0) {
                            $("#comment-" + data.reply_to).after(data.template);
                            moveCommentForm(data.reply_to);
                        } else
                            $(".comment-list").append(data.template);
                        if (document.getElementById("no-comment")) {
                            $("#no-comment").hide();
                        }
                        $("#comment-content").val("");
                    } else {
                        alert(data.info);
                    }
                }
            },
            'error': function (data, status) {
                alert(status);
            },
            'complete': function () {
                afterTransfer();
                $("input[name*=captcha]").val("");
                $(".captcha-cover img").click();
            }
        });
        return false;
    });
    //判断URL
    $("#Comment-url").focus(function () {
        if ($(this).val() == "") {
            $(this).val("http://");
        }
    }).blur(function () {
        var url = $(this).val();
        if (url == "http://") {
            $(this).val("");
            url = "";
        }
        if (url.length > 0) {
            if (checkURL(url)) {
                $(this).parent(".input-group").removeClass("has-error");
                $(this).parent(".input-group").addClass("has-success");
            } else {
                $(this).parent(".input-group").removeClass("has-success");
                $(this).parent(".input-group").addClass("has-error");
            }
        } else {
            $(this).parent(".input-group").removeClass("has-success has-error");
        }
    });
});
function onTransfer() {
    $("#sendComment").text('提交中...');
    $('<div id="onTransfer"><span></span></div>').appendTo(".leave-comment");
    $("#onTransfer").show();
}
function afterTransfer() {
    $("#sendComment").text('提交留言');
    $("#onTransfer").remove();
}
/**
 * 检测必填项
 */
function checkRequire() {
    var empty = false;
    var comment_error = $("#comment-error");
    comment_error.html("");
    $(".leave-comment input[required=required]").each(function () {
        if (!$(this).val()) {
            comment_error.append("<p>* " + $(this).attr("data-title") + "不能为空。</p>");
            empty = true;
        }
    });
    var url = $("#comment-url").val();
    if (url && !checkURL(url)) {
        comment_error.append("<p>* URL格式错误，请以http或者https开头。</p>");
        empty = true;
    }
    if (!$("#comment-content").val()) {
        comment_error.append("<p>* 评论内容不能为空。</p>");
        empty = true;
    }
    return empty;
}
/**
 * 回复
 * @param cid CommentId
 */
function moveCommentForm(cid) {
    //如果是点击的回复留言，那么要判断二次点击取消回复
    var comment_area = $("#comment");
    if ($("#comment-" + cid).has("#comment").length > 0) {
        comment_area.hide().appendTo("#comment-area");
        $("#parentId").val(0);
        $("#comment-" + cid + " .comment-reply-link").text("回复");
    } else {
        comment_area.hide().appendTo("#comment-" + cid);
        $("#parentId").val(cid);
        $("#comment-" + cid + " .comment-reply-link").text("收起回复");
    }
    comment_area.slideDown(300);
}
/**
 * 正则匹配URL
 * @param url
 * @returns {boolean}
 */
function checkURL(url) {
    var Expression = /^http(s)?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i;
    var objExp = new RegExp(Expression);
    return objExp.test(url);
}