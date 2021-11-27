update_chat();

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function updateChatMargin() {
    var
        dadCont = $('.dad-container'),
        dadContHeight = dadCont.innerHeight(),
        windowHeight = $(window).innerHeight();

    if (dadContHeight <= windowHeight) {
        $('#chatContainer').css({"margin-top": 0});
    }
}

function updateChatScroll() {
    var
        chatScroll = $('#chatScroll'),
        windowHeight = $(window).innerHeight(),
        chatInput = $('#chatInput'),
        chatForm = $('.chat-form'),
        chatNotLogged = $('#notLoggedIn'),
        chatPrompt = $('.chat-prompt'),
        chatHeight = windowHeight;

    if (chatInput.length) {
        chatHeight = chatHeight - chatForm.innerHeight() - 18;
    }
    else {
        chatHeight = chatHeight - chatNotLogged.innerHeight() - 15;
    }

    if (chatPrompt.length) {
        chatHeight = chatHeight - chatPrompt.innerHeight();
    }

    
}
function toggleChat() {
    //Config variable
    var
        mainContainer = $('.main-container'),
        dadContainer = $('.dad-container'),
        chatBody = $('#chatBody'),
        chatHeader = $('#chatHeader'),
        chatClose = $('#chatClose'),
        chatContainer = $('#chatContainer'),
        chatScroll = $('#chatScroll'),
        viewPortHeight = $(window).innerHeight(),
        viewPortWidth = $(window).innerWidth();

    //Set to chatContainer like viewPortHeight
    chatContainer.css({"height": viewPortHeight});

    $(window).resize(function () {
        viewPortHeight = $(window).innerHeight();
        chatContainer.css({"height": viewPortHeight});
    });

    //For test
    $('body').append(chatHeader);

    if (getCookie('chat') !== '0') {
        //Add classes when the page is loaded
        mainContainer
            .addClass('with-chat')
            .find('.dad-container')
            .addClass('with-chat');

        //Show container with chat
        chatContainer.show();
    }
    else {
        chatHeader.fadeIn();
    }

    //Set viewport height
    setTimeout(updateChatScroll, 0);

    //Call perfectBar
    chatScroll.perfectScrollbar();
    // chatScroll.scrollTop( chatScroll.prop( "scrollHeight" ) );
    // chatScroll.perfectScrollbar('update');

    //Events

    //Close chat
    chatClose.on('click', function (e) {
        e.preventDefault();

        document.cookie = "chat=0";

        chatContainer.animate({width: 'toggle'}, 400, function () {
            //Change viewport
            $('meta[name=viewport]').attr('content', 'width=1050');

            mainContainer
                .toggleClass('with-chat')
                .find('.dad-container')
                .toggleClass('with-chat');

            chatHeader.fadeIn();
        });
    });

    //Open chat
    chatHeader.on('click', function (e) {
        e.preventDefault();
        $(this).fadeOut();

        document.cookie = "chat=1";

        mainContainer.removeClass('big-padding');

        //Change viewport
        $('meta[name=viewport]').attr('content', 'width=1280');

        mainContainer
            .toggleClass('with-chat')
            .find('.dad-container')
            .toggleClass('with-chat');
        chatContainer.animate({width: 'toggle'}, 400);
    });

    //Scroll event, emulation fixed block;

    $(window).bind('scroll.chatScroll', function () {
        var dadHeight = dadContainer.innerHeight(),
            chatContHeight = chatContainer.innerHeight(),
            scrollTop = $(this).scrollTop();

        if (dadHeight > chatContHeight) {
            chatContainer.css({
                "margin-top": scrollTop
            });
        }
    });

    //If user screen size < 1360 hidden chat
    if (viewPortWidth < 1360) {
        chatClose.trigger('click');
    }
}

$(function () {
    if (document.location.pathname === "/") toggleChat();
});


$(function () {

    $('#chatInput').keypress(function (e) {
        if (!e.shiftKey && e.which == 13) {
            sendMessage($(this).val());
            $(this).val('');
            e.preventDefault();
        }
    });

    $('.chat-submit-btn').click(function (e) {
        sendMessage($('#chatInput').val());
        e.preventDefault();
    });

    $('#chatRules').click(function () {
        $('#chatRulesModal').arcticmodal();
    });
});

var lastMsg = '';
var lastMsgTime = '';

function sendMessage(text) {
    $.post('/add_message', {messages: text}, function (message) {
        if (message && message.status) {
            $.notify(message.message, message.status);
            $('#chatInput').val('');
        }
    });
}

function update_chat() {
    $.ajax({
        type: "GET",
        url: "/chat",
        dataType: "json",
        cache: false,
        success: function (message) {

            if (message && message.length > 0) {
                $('#messages').html('');
                message = message.reverse();
                for (var i in message) {
                    var a = $("#chatScroll")[0];
                    //var isScrollDown = (a.offsetHeight + a.scrollTop) == a.scrollHeight;
                    var isScrollDown = Math.abs((a.offsetHeight + a.scrollTop) - a.scrollHeight) < 5;

                    if (message[i].admin != 1) {
                        var html = '<div class="message" data-uuid="' + message[i].id + '" data-user="' + message[i].userid + '"><a href="/user/' + message[i].userid + '" target="_blank"><img src="https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/' + _.escape(message[i].avatar).replace('_full', '') + '"><div class="name">' + message[i].username + '</div></a><div class="2tck" style="float: left;height: 11px;line-height: 25px;font-size: 12px;padding: 0 5px;">:</div>'+ message[i].messages + '</div>';
//                        html += '<a href="/user/' + message[i].userid + '" target="_blank"><img src="https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/' + _.escape(message[i].avatar).replace('_full', '') + '"></a>';
//                        html += '<div class="login" href="/user/' + message[i].userid + '" target="_blank">' + message[i].username + '</div>';
//                        html += '<div class="body">' + message[i].messages + '</div>';
//                        html += '</div>';
                    }
                    else {
                        var html = '<div class="message" ata-uuid="' + message[i].id + '"><img src="https://cdn0.iconfinder.com/data/icons/financial-icons/475/user.png"><div class="name" style="color: #F57575;">Администратор</div><div class="2tck" style="float: left;height: 11px;line-height: 25px;font-size: 12px;padding: 0 5px;">:</div>'+ message[i].messages + '</div>';
                    }

                    $('#messages').append(html);
                    if ($('.chatMessage').length > 100) {
                        $('.chatMessage').eq(0).remove();
                    }
                }

                if (isScrollDown) a.scrollTop = a.scrollHeight;
                $("#chatScroll").perfectScrollbar('update');
            }
        }
    });
}