$(document).ready(function () {

    $('a[href="' + document.location.pathname + '"]').parent().addClass('active');

    $('.history-block-item .user .username').each(function () {
        $(this).text(replaceLogin($(this).text()));
    });

    $('.deposit-item:not(.card)').tooltip({container: 'body'});

    $('[data-toggle="popover"]').popover({
        "container": "body"
    });

    EZYSKINS.init();

    $('.close-eto-delo').click(function (e) {
        $(this).parent('.msg-wrap').slideUp();
    });
    
    $('.no-link').click(function() {
        $('.trade-modal').arcticmodal();
    });


    $(document).on('click', '#cardDepModal', function () {
        $('#cardDepositModal').arcticmodal();
    });
    
    $('#balModal').click(function() {
        $('#balance-modal').arcticmodal(); 
    });
    $('#balModal2').click(function() {
        $('#balance-modal').arcticmodal(); 
    });

    $(document).on('click', '.deposit-no-link', function () {
        $('#linkBlock').slideDown();
        return false;
    });

    $(document).on('click', '.tooltip-btn.level', function () {
        $('.profile-level').tooltip('hide');
        $('#level-popup').arcticmodal();
    });
    $(document).on('click', '.tooltip-btn.card', function () {
        $('.deposit-item.card').tooltip('hide');
        $('#card-popup').arcticmodal();
    });
    $(document).on('click', '.tooltip-btn.ticket', function () {
        $('.ticket-number').tooltip('hide');
        $('#ticket-popup').arcticmodal();
    });
    $(document).on('click', '#user-level-btn', function () {
        $('.level-badge').tooltip('hide');
        $('#level-popup').arcticmodal();
    });

    $('.tooltip').remove();

    $('.ticket-number').tooltip({
        html: true,
        trigger: 'hover',
        delay: {show: 50, hide: 200},
        title: function () {
            var text = '1 билет = 1 коп';
            var btn = 'для чего нужен билет?';

            return '<span class="tooltip-title ticket">' + text + '</span><br/><span class="tooltip-btn ticket">' + btn + '</span>';
        }
    });

    $('.deposit-item:not(.card)').tooltip({
        container: 'body',
        //delay: {show: 50, hide: 200}
    });

    $('.deposit-item.card').each(function () {
        var that = $(this);
        that.data('old-title', that.attr('title'));
        that.attr('title', null);
        that.tooltip({
            html: true,
            trigger: 'hover',
            delay: {show: 50, hide: 200},
            title: function () {
                var text = $(this).data('old-title');
                var btn = 'для чего нужны карточки?';

                return '<span class="tooltip-title card">' + text + '</span><br/><span class="tooltip-btn card">' + btn + '</span>';
            }
        });
    });


    $('.save-trade-link-input')
        .keypress(function (e) {
            if (e.which == 13) $(this).next().click()
        })
        .on('paste', function () {
            var that = $(this);
            setTimeout(function () {
                that.next().click();
            }, 0);
        });

    $('.save-trade-link-input-btn').click(function () {
        var that = $(this).prev();
        $.ajax({
            url: '/settings/save',
            type: 'POST',
            dataType: 'json',
            data: {trade_link: $(this).prev().val()},
            success: function (data) {
                if (data.status == 'success') {
                    $('#linkBlock').slideUp();
                    $('.no-link').removeClass('no-link');
                    if (data.msg) return $.notify(data.msg, 'success');
                }
                if (data.msg) $.notify(data.msg, 'error');
            },
            error: function () {
                ajaxError();
            }
        });
        return false;
    });

    $('.tooltip').remove();
    $('.current-user').tooltip({container: 'body'});

});

function updateBackground() {
    var mainHeight = $('.dad-container').height();
    var windowHeight = $(window).height();

    if (mainHeight > windowHeight) {
        $('.main-container').height('auto');
    }
    else {
        $('.main-container').height('100%');
    }
}

function replaceLogin(login) {
    function replacer(match, p1, p2, p3, offset, string) {
        var links = ['csgohax.ru', 'csgohax.ru'];
        return links.indexOf(match.toLowerCase()) == -1 ? '' : match;
    }

    login = login.replace('сom', 'com').replace('cоm', 'com').replace('соm', 'com');
    var res = login.replace(/([а-яa-z0-9-]+) *\. *(ru|com|net|gl|su|red|ws|us)+/i, replacer);
    if (!res.trim()) {

        var check = login.toLowerCase().split('csgohax.ru').length > 1 || login.toLowerCase().split('csgohax.ru').length > 1;

        if (check) {
            res = login;
        }
        else {
            res = login.replace(/csgo/i, '').replace(/ *\. *ru/i, '').replace(/ *\. *com/i, '');
            if (!res.trim()) {
                res = 'UNKNOWN';
            }
        }
    }

    res = res.split('script').join('srcipt');
    return res;
}

function updateScrollbar() {
    $('.current-chance-block').perfectScrollbar('destroy');
    $('.current-chance-block').perfectScrollbar({suppressScrollY: true, useBothWheelAxes: true});
}

updateScrollbar();
updateBackground();

function getRarity(type) {
    var rarity = '';
    var arr = type.split(',');
    if (arr.length == 2) type = arr[1].trim();
    if (arr.length == 3) type = arr[2].trim();
    if (arr.length && arr[0] == 'Нож') type = '★';
    switch (type) {
        case 'Армейское качество':
            rarity = 'milspec';
            break;
        case 'Запрещенное':
            rarity = 'restricted';
            break;
        case 'Засекреченное':
            rarity = 'classified';
            break;
        case 'Тайное':
            rarity = 'covert';
            break;
        case 'Ширпотреб':
            rarity = 'common';
            break;
        case 'Промышленное качество':
            rarity = 'common';
            break;
        case '★':
            rarity = 'rare';
            break;
        case 'card':
            rarity = 'card';
            break;
    }
    return rarity;
}

function n2w(n, w) {
    n %= 100;
    if (n > 19) n %= 10;

    switch (n) {
        case 1:
            return w[0];
        case 2:
        case 3:
        case 4:
            return w[1];
        default:
            return w[2];
    }
}

function lpad(str, length) {
    while (str.toString().length < length)
        str = '0' + str;
    return str;
}

$(document).on('click', '#showUsers, #showItems', function () {
    if ($(this).is('.active')) return;

    $('#showUsers, #showItems').removeClass('active');
    $(this).addClass('active');

    $('#usersChances .users').toggle();
    $('#usersChances .items').toggle();
    updateScrollbar();
});

$('#usersChances').hover(function () {
    var block = $('#showUsers').is('.active') ? $('.current-chance-block.users') : $('.current-chance-block.items');
    var min = $('#showUsers').is('.active') ? 10 : 9;

    if (block.find('.current-chance-wrap').children().length > min) $('.arrowscroll').show();
}, function () {
    $('.arrowscroll').hide();
});
$('.arrowscroll').click(function () {
    var block = $('#showUsers').is('.active') ? $('.current-chance-block.users') : $('.current-chance-block.items');
    var direction = $(this).is('.left') ? '-' : '+';

    block
        .stop(true, false)
        .animate({scrollLeft: direction + "=250"});
});

if (START) {
    updateBackground();
    var socket = io.connect(':2020');

    if (checkUrl()) {
        socket
            .on('connect', function () {
                $('#loader').hide();
            })
            .on('disconnect', function () {
                $('#loader').show();
            })
            .on('online', function (data) {
                $('#online').text(Math.abs(data));
            })
            .on('newDeposit', function (data) {
                updateBackground();
                data = JSON.parse(data);
                $('#bets').prepend(data.html);
                var username = $('#bet_' + data.id + ' .history-block-item .user .username').text();
                $('#bet_' + data.id + ' .history-block-item .user .username').text(replaceLogin(username));
                $('#roundBank').text(Math.round(data.gamePrice));
                $('title').text(Math.round(data.gamePrice) + ' руб - CS:GO');
                $('#items').html(data.itemsCount);
                $('.item-bar').css('width', data.itemsCount + '%');
                $('.deposit-item').tooltip({container: 'body', placement: 'top'});

                console.log(data.chances);
                html_chances = '';

                data.chances = sortByChance(data.chances);
                data.chances.forEach(function (info) {
                    if (USER_ID == info.steamid64) {
                        $('#myChance').text(info.chance + '%');
                    }
                    $('.id-' + info.steamid64).text(info.chance + '%');
                    html_chances += '<div class="current-user" title="' + replaceLogin(info.username) + '"><a class="img-wrap" href="/user/' + info.steamid64 + '" target="_blank"><img src="' + info.avatar + '" /></a><div class="chance">' + info.chance + '%</div></div>';
                });

                $('#usersChances .users .current-chance-wrap').html(html_chances);
                $('#usersChances').slideDown();

                $('.tooltip').remove();
                $('.current-user').tooltip({container: 'body'});

                EZYSKINS.initTheme();
            })
            .on('forceClose', function () {
                $('.forceClose').removeClass('msgs-not-visible');
            })
            .on('timer', function (time) {
                $('#timer').text(lpad(time - Math.floor(time / 90) * 90, 2));
            })
            .on('slider', function (data) {

                // Таймер
                $('#newGameTimer .countSeconds').text(lpad(data.time - Math.floor(data.time / 60) * 60, 2));

                if (ngtimerStatus) {
                    ngtimerStatus = false;
                    var users = data.users;

                    users = mulAndShuffle(users, Math.ceil(130 / users.length));
                    users[112] = data.winner;
                    html = '';
                    users.forEach(function (i) {
                        html += '<li><img src="' + i.avatar + '"></li>';
                    });
                    $('#usersCarousel').html(html);

                    $('#barContainer').hide();
                    $('#usersChances').hide();
                    $('.game-info').show();
                    $('#usersCarouselConatiner').show();
                    
                    if (data.showCarousel) {
                        $('#depositButtonsBlock').slideUp();
                    }
                    else {
                        $('#depositButtonsBlock').hide();
                    }

                    $('#winnerInfo').show();

                    fillWinnerInfo(data);

                    var audio = new Audio('assets/sounds/tone.wav');
                    audio.play();
                    $('.depCards').hide();
                    $('.winnerset').hide();
                    $('#usersCarousel').css('margin-left', -41);
                    if (data.showSlider) {
                        $('#usersCarousel').animate(
                            {marginLeft: -7272}, 1000 * 8,
                            function () {
                                $('#roundNum').text(data.round_number);
                            });
                    }
                    function fillWinnerInfo(data) {
                        data = data || {winner: {}};
                        $('#roundNum').text(data.round_number);
                        var obj = {
                            totalPrice: data.game.price || 0,
                            number: data.game.price ? ('#' + Math.floor(data.round_number * data.game.price)) : '???',
                            tickets: data.tickets || 0,
                            winner: {
                                image: data.winner.avatar || '???',
                                login: data.winner.username || '???',
                                id: data.winner.steamid64 || '#',
                                chance: data.chance || 0,
                                winTicket: data.ticket || '???'
                            }
                        };
                        
                        setTimeout(winnerinfoset,8000);
                        function winnerinfoset() {
                        $('.timeTime').fadeOut(200).hide();
                        $('.timWinner').fadeIn(200).show();
                        $('.game-number').slideDown();
                        $('#WinTicket').text('#' + obj.winner.winTicket);
                        // $('#winnerInfo #totalTickets').text(obj.tickets);
                        $('#WinImg img').attr('src', obj.winner.image);
                        $('#winnerInfo #winnerLink').text(replaceLogin(obj.winner.login));
                        $('#WinLink').attr('href', '/user/' + obj.winner.id);
                        $('#WinLink2').attr('href', '/user/' + obj.winner.id);
                        $('#WinChance').text(obj.winner.chance.toFixed(2));
                        $('#WinChance2').text(obj.winner.chance.toFixed(2));
                        $('#WinBank').text(obj.totalPrice);
                        $('#WinBank2').text(obj.totalPrice);
                        $('#WinName').text(obj.winner.login);
                        $('#WinName2').text(obj.winner.login);
                        }
                    }
                }
            })
            .on('newGame', function (data) {
                var audio = new Audio('assets/sounds/start.mp3');
                audio.play();
                $('.timWinner').fadeOut(200)
                $('.timWinner').hide();
                $('.timeTime').fadeIn(200)
                $('.timeTime').show();
                $('.depCards').slideDown();
                $('.winnerset').slideDown();
                $('#roundNum').hide();
                $('.game-hash').slideDown();
                $('.game-number').hide();
                $('.game-info').hide();
                $('#usersChances .users .current-chance-wrap').html('');
                $('#usersChances').hide();
                $('#bets').html('');
                $('#myChance').text('0%');
                $('#roundId').text(data.id);
                $('#roundBank').text('0');
                $('#roundHash').text(data.hash);
                $('#items').html('0');
                $('#roundFinishBlock').hide();
                $('#barContainer').show();
                $('#usersCarouselConatiner').hide();
                $('#depositButtonsBlock').slideDown();
                $('#winnerInfo').hide();
                $('#timer').text('90');
                $('title').text('0 руб - CS:GO');
                ngtimerStatus = true;
            })
            .on('queue', function (data) {
                console.log(data);
                if (data) {
                    var n = data.indexOf(USER_ID);
                    if (n !== -1) {
                        $.notify('Ваш депозит обрабатывается. Вы ' + (n + 1) + ' в очереди.', {
                            clickToHide: 'false',
                            autoHide: "false",
                            className: "success"
                        });
                    }
                }
            })
            .on('chat_messages', function (data) {
                message = data;
                if (message && message.length > 0) {
                    $('#messages').html('');
                    message = message.reverse();
                    for (var i in message) {
                        var a = $("#chatScroll")[0];
                        //var isScrollDown = (a.offsetHeight + a.scrollTop) == a.scrollHeight;
                        var isScrollDown = Math.abs((a.offsetHeight + a.scrollTop) - a.scrollHeight) < 5;

                        if (message[i].admin != 1) {
                        var html = '<div class="message" data-uuid="' + message[i].id + '" data-user="' + message[i].userid + '"><a href="/user/' + message[i].userid + '" target="_blank"><img src="https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/' + _.escape(message[i].avatar).replace('_full', '') + '"><div class="name">' + message[i].username + '</div></a><div class="2tck" style="float: left;height: 11px;line-height: 25px;font-size: 12px;padding: 0 5px;">:</div>'+ message[i].messages + '</div>';
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
            })
            .on('depositDecline', function (data) {
                data = JSON.parse(data);
                if (data.user == USER_ID) {
                    clearTimeout(declineTimeout);
                    declineTimeout = setTimeout(function () {
                        $('#errorBlock').slideUp();
                    }, 1000 * 10)
                    $('#errorBlock p').text(data.msg);
                    $('#errorBlock').slideDown();
                }
            })
    }
    else {
        socket
            .on('online', function (data) {
                $('#online').text(Math.abs(data));
            })
            .on('queue', function (data) {
                console.log(data);
                if (data) {
                    var n = data.indexOf(USER_ID);
                    if (n !== -1) {
                        $.notify('Ваш депозит обрабатывается. Вы ' + (n + 1) + ' в очереди.', {
                            clickToHide: 'false',
                            autoHide: "false",
                            className: "success"
                        });
                    }
                }
            })
    }
    var declineTimeout,
        timerStatus = true,
        ngtimerStatus = true;
}

function loadMyInventory() {
    $('thead').hide();
    $.ajax({
        url: '/myinventory',
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            var text = '<tr><td colspan="4" style="text-align: center">РџСЂРѕРёР·РѕС€Р»Р° РѕС€РёР±РєР°. РџРѕРїСЂРѕР±СѓР№С‚Рµ РµС‰Рµ СЂР°Р·</td></tr>';
            var totalPrice = 0;

            if (!data.success && data.Error) text = '<tr><td colspan="4" style="text-align: center">' + data.Error + '</td></tr>';

            if (data.success && data.rgInventory && data.rgDescriptions) {
                text = '';
                var items = mergeWithDescriptions(data.rgInventory, data.rgDescriptions);
                console.table(items);
                items.sort(function (a, b) {
                    return parseFloat(b.price) - parseFloat(a.price)
                });
                _.each(items, function (item) {
                    item.price = item.price || 0;
                    totalPrice += parseFloat(item.price);
                    item.price = item.price;
                    item.image = 'https://steamcommunity-a.akamaihd.net/economy/image/class/730/' + item.classid + '/200fx200f';
                    item.market_name = item.market_name || '';
                    text += ''
                        + '<div class="item-inv">'
                        + '<div class="img">'
                        + '<img src="' + item.image + '">'
                        + '</div>'
                        + '<div class="name">' + item.name + '' + item.market_name.replace(item.name, '').replace('(', '[').replace(')', ']') + '</div>'
                        + '<div class="price">' + (item.price || '0.00') + ' <span>Р</span>'
                        + '</div>'
                        + '</div>'
                });
                $('#totalPrice').text(totalPrice.toFixed(2));
                $('.item-list-inv').show();
            }

            $('.item-list-inv').html(text);
            updateBackground();
        },
        error: function () {
            var text = isEn() ? 'An error has occurred. Try again' : 'РџСЂРѕРёР·РѕС€Р»Р° РѕС€РёР±РєР°. РџРѕРїСЂРѕР±СѓР№С‚Рµ РµС‰Рµ СЂР°Р·';
            $('tbody').html('<tr><td colspan="4" style="text-align: center">' + text + '<td></tr>');
        }
    });
}

function mergeWithDescriptions(items, descriptions) {
    return Object.keys(items).map(function (id) {
        var item = items[id];
        var description = descriptions[item.classid + '_' + (item.instanceid || '0')];
        for (var key in description) {
            item[key] = description[key];

            delete item['icon_url'];
            delete item['icon_drag_url'];
            delete item['icon_url_large'];
        }
        return item;
    })
}

function mulAndShuffle(arr, k) {
    var
        res = [],
        len = arr.length,
        total = k * len,
        rand, prev;
    while (total) {
        rand = arr[Math.floor(Math.random() * len)];
        if (len == 1) {
            res.push(prev = rand);
            total--;
        }
        else if (rand !== prev) {
            res.push(prev = rand);
            total--;
        }
    }
    return res;
}

$(document).on('click', '.vote', function () {
    var that = $(this);
    $.ajax({
        url: '/ajax',
        type: 'POST',
        dataType: 'json',
        data: {action: 'voteUser', id: $(this).data('profile')},
        success: function (data) {
            if (data.status == 'success') {
                $('#myProfile').find('.votes').text(data.votes || 0);
            }
            else {
                if (data.msg) that.notify(data.msg, {position: 'bottom middle', className: "error"});
            }
        },
        error: function () {
            that.notify("Произошла ошибка. Попробуйте еще раз", {position: 'bottom middle', className: "error"});
        }
    });
});

function sortByChance(arrayPtr) {
    var temp = [],
        item = 0;
    for (var counter = 0; counter < arrayPtr.length; counter++) {
        temp = arrayPtr[counter];
        item = counter - 1;
        while (item >= 0 && arrayPtr[item].chance < temp.chance) {
            arrayPtr[item + 1] = arrayPtr[item];
            arrayPtr[item] = temp;
            item--;
        }
    }
    return arrayPtr;
}

function checkUrl() {
    var pathname = window.location.pathname;

    if (pathname.indexOf('game') + 1) {
        return false;
    }
    else {
        return true;
    }

}

function formatDate(date) {
    moment(date).format('DD/MM/YYYY - <span>h:mm</span>');
}

$.notify.addStyle('custom', {html: "<div>\n<span data-notify-text></span>\n</div>"});
$.notify.defaults({style: "custom"});

$(document).on('mouseenter', '.iusers, .iskins', function () {
    $(this).tooltip('show');
});