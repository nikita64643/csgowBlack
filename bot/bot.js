var fs = require('fs');
var crypto = require('crypto');
var console = process.console;
var config = require('./config.js');
var Steam = require('steam');
var SteamWebLogOn = require('steam-weblogon');
var getSteamAPIKey = require('steam-web-api-key');
var SteamTradeOffers = require('steam-tradeoffers');
var SteamCommunity = require('steamcommunity');
var SteamcommunityMobileConfirmations = require('steamcommunity-mobile-confirmations');
var SteamTotp = require('steam-totp');
var redisClient, io, requestify;

module.exports.init = function (redis, ioSocket, requestifyCore) {
    io = ioSocket;
    redisClient = redis.createClient();
    requestify = requestifyCore;
}

var details = {
    account_name: config.bot.username,
    password: config.bot.password,
    two_factor_code: generatekey(config.bot.secret)
};

var steamClient = new Steam.SteamClient();
var steamUser = new Steam.SteamUser(steamClient);
var steamFriends = new Steam.SteamFriends(steamClient);
var steamWebLogOn = new SteamWebLogOn(steamClient, steamUser);
var offers = new SteamTradeOffers();

// Generation Device_ID
var hash = require('crypto').createHash('sha1');
hash.update(Math.random().toString());
hash = hash.digest('hex');
var device_id = 'android:' + hash;

var checkingOffers = [],
    WebCookies = [],
    WebSession = false,
    globalSession;

const redisChannels = {
    checkItemsList: 'checkItems.list',
    checkList: 'check.list',
    checkedList: 'checked.list',
    betsList: 'bets.list',
    sendOffersList: 'send.offers.list',
    tradeoffersList: 'tradeoffers.list',
    declineList: 'decline.list',
    usersQueue: 'usersQueue.list'
}

function steamBotLogger(log) {
    console.tag('SteamBot').log(log);
}

function EscrowLogger(log) {
    console.tag('EscrowBot').info(log);
}

function generatekey(secret) {
    code = SteamTotp.generateAuthCode(secret);
    EscrowLogger('Generated Code : ' + code);
    return code;
}

steamClient.connect();
steamClient.on('debug', steamBotLogger);
steamClient.on('connected', function () {
    steamUser.logOn(details);
});

steamClient.on('logOnResponse', function (logonResp) {
    if (logonResp.eresult === Steam.EResult.OK) {
        steamBotLogger('Logged in!');
        steamFriends.setPersonaState(Steam.EPersonaState.Online);

        steamWebLogOn.webLogOn(function (sessionID, newCookie) {
            getSteamAPIKey({
                sessionID: sessionID,
                webCookie: newCookie
            }, function (err, APIKey) {
                offers.setup({
                    sessionID: sessionID,
                    webCookie: newCookie,
                    APIKey: APIKey
                }, function (err) {
                    WebSession = true;
                    globalSession = sessionID;
                    WebCookies = newCookie;
                    redisClient.lrange(redisChannels.tradeoffersList, 0, -1, function (err, offers) {
                        offers.forEach(function (offer) {
                            checkingOffers.push(offer);
                        });
                        handleOffers();
                        AcceptMobileOffer();
                    });
                    steamBotLogger('Setup Offers!');
                });

            });
        });
    }
});

function reWebLogon() {
    steamWebLogOn.webLogOn(function (sessionID, newCookie) {
        getSteamAPIKey({
            sessionID: sessionID,
            webCookie: newCookie
        }, function (err, APIKey) {
            offers.setup({
                sessionID: sessionID,
                webCookie: newCookie,
                APIKey: APIKey
            }, function (err) {
                WebSession = true;
                globalSession = sessionID;
                WebCookies = newCookie;
                steamBotLogger('WebSession Reloaded !');
            });
        });
    });
}

function handleOffers() {
    offers.getOffers({
        get_received_offers: 1,
        active_only: 1
    }, function (error, body) {
        if (
            body
            && body.response
            && body.response.trade_offers_received
        ) {
            body.response.trade_offers_received.forEach(function (offer) {
                if (offer.trade_offer_state == 2) {
                    if (is_checkingOfferExists(offer.tradeofferid)) return;

                    if (offer.items_to_give != null && config.admins.indexOf(offer.steamid_other) != -1) {
                        console.tag('SteamBot', 'TradeOffer').log('TRADE OFFER #' + offer.tradeofferid + ' FROM: Admin ' + offer.steamid_other);
                        offers.acceptOffer({tradeOfferId: offer.tradeofferid});
                        return;
                    }
                    if (offer.items_to_give != null) {
                        offers.declineOffer({tradeOfferId: offer.tradeofferid});
                        return;
                    }
                    offers.getTradeHoldDuration({tradeOfferId: offer.tradeofferid}, function (err, hold) {
                        if (err) {
                            reWebLogon();
                            steamBotLogger('Check hold error: ' + err);
                            setTimeout(function () {
                                offers.getTradeHoldDuration({tradeOfferId: offer.tradeofferid}, function (err, hold) {
                                    if (err) {
                                        offers.declineOffer({tradeOfferId: offer.tradeofferid});
                                        reWebLogon();
                                        steamBotLogger('Check hold error: ' + err);
                                    } else {
                                        if (hold.their != 0) {
                                            offers.declineOffer({tradeOfferId: offer.tradeofferid});
                                            steamBotLogger('Escrow offer declined: ' + offer.tradeofferid);
                                        }
                                    }
                                });
                            }, 5000);
                        } else {
                            if (hold.their != 0) {
                                offers.declineOffer({tradeOfferId: offer.tradeofferid});
                                steamBotLogger('Escrow offer declined: ' + offer.tradeofferid);
                            }
                        }
                    });
                    if (offer.items_to_receive != null && offer.items_to_give == null) {
                        checkingOffers.push(offer.tradeofferid);
                        console.tag('SteamBot', 'TradeOffer').log('TRADE OFFER #' + offer.tradeofferid + ' FROM: ' + offer.steamid_other);
                        redisClient.multi([
                            ['rpush', redisChannels.tradeoffersList, offer.tradeofferid],
                            ['rpush', redisChannels.checkItemsList, JSON.stringify(offer)],
                            ['rpush', redisChannels.usersQueue, offer.steamid_other]
                        ]).exec(function () {
                            redisClient.lrange(redisChannels.usersQueue, 0, -1, function (err, queues) {
                                io.sockets.emit('queue', queues);
                            });
                        });
                        return;
                    }
                }
            });
        }
    });
}

steamUser.on('tradeOffers', function (number) {
    if (number > 0) {
        handleOffers();
    }
});


var parseOffer = function (offer, offerJson) {
    offers.loadPartnerInventory({
        partnerSteamId: offer.steamid_other,
        appId: 730,
        contextId: 2,
        tradeOfferId: offer.tradeofferid,
        language: "russian"
    }, function (err, hitems) {
        if (err) {
            redisClient.multi([
                ['rpush', redisChannels.declineList, offer.tradeofferid],
                ['lrem', redisChannels.checkItemsList, 0, offerJson],
                ['lrem', redisChannels.usersQueue, 1, offer.steamid_other]
            ])
                .exec(function (err, replies) {
                    parseItemsProcceed = false;
                    return;
                });
            return;
        }
        var items = offer.items_to_receive;
        var items_to_check = [], num = 0;
        for (var i = 0; i < items.length; i++) {
            for (var j = 0; j < hitems.length; j++) {
                if (items[i].assetid == hitems[j].id) {
                    items_to_check[num] = {
                        appid: hitems[j].appid,
                        name: hitems[j].market_name,
                        market_hash_name: hitems[j].market_hash_name,
                        classid: hitems[j].classid
                    };
                    var type = hitems[j].type;
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
                    }
                    items_to_check[num].rarity = rarity;
                    num++;
                    break;
                }
            }
        }
        var value = {
            offerid: offer.tradeofferid,
            accountid: offer.steamid_other,
            items: JSON.stringify(items_to_check)
        };

        console.tag('SteamBot', 'Offer #' + value.offerid).log(value);

        redisClient.multi([
            ['rpush', redisChannels.checkList, JSON.stringify(value)],
            ['lrem', redisChannels.checkItemsList, 0, offerJson]
        ])
            .exec(function (err, replies) {
                parseItemsProcceed = false;
            });

    });
}

var checkOfferPrice = function () {
    requestify.post('http://' + config.domain + '/api/checkOffer', {
        secretKey: config.secretKey
    })
        .then(function (response) {
            var answer = JSON.parse(response.body);

            if (answer.success) {
                checkProcceed = false;
            }
        }, function (response) {
            console.tag('SteamBot').error('Something wrong with check offers. Retry...');
            setTimeout(function () {
                checkOfferPrice()
            }, 1000);
        });

}

var checkNewBet = function () {
    requestify.post('http://' + config.domain + '/api/newBet', {
        secretKey: config.secretKey
    })
        .then(function (response) {
            var answer = JSON.parse(response.body);
            if (answer.success) {
                betsProcceed = false;
            }
        }, function (response) {
            console.tag('SteamBot').error('Something wrong with send a new bet. Retry...');
            setTimeout(function () {
                checkNewBet()
            }, 1000);
        });
}

var checkArrGlobal = [];

var sendTradeOffer = function (appId, partnerSteamId, accessToken, sendItems, message, game, offerJson) {
    try {
        offers.loadMyInventory({
            appId: appId,
            contextId: 2
        }, function (err, items) {
            if (err) {
                console.log(err);
                sendProcceed = false;
                return;
            }
            var itemsFromMe = [],
                checkArr = [],
                num = 0;
            var i = 0;
            for (var i = 0; i < sendItems.length; i++) {
                for (var j = 0; j < items.length; j++) {
                    if (items[j].tradable && (items[j].classid == sendItems[i])) {
                        if ((checkArr.indexOf(items[j].id) == -1) && (checkArrGlobal.indexOf(items[j].id) == -1)) {
                            checkArr[i] = items[j].id;
                            itemsFromMe[num] = {
                                appid: 730,
                                contextid: 2,
                                amount: items[j].amount,
                                assetid: items[j].id
                            };
                            num++;
                            break;
                        }
                    }
                }
            }
            if (num > 0) {
                offers.makeOffer({
                    partnerSteamId: partnerSteamId,
                    accessToken: accessToken,
                    itemsFromMe: itemsFromMe,
                    itemsFromThem: [],
                    message: 'Поздравляем с победой на сайте ' + config.nameSite + ' | В игре #' + game
                }, function (err, response) {
                    if (err) {
                        console.tag('SteamBot', 'SendPrize').error('Error to send offer:' + err.message);
                        getErrorCode(err.message, function (errCode) {
                            if (errCode == 15 || errCode == 25 || err.message.indexOf('an error sending your trade offer.  Please try again later.')) {
                                redisClient.lrem(redisChannels.sendOffersList, 0, offerJson, function (err, data) {
                                    setPrizeStatus(game, 2);
                                    sendProcceed = false;
                                });
                                sendProcceed = false;
                            }
                            sendProcceed = false;
                        });
                        return;
                    }
                    checkArrGlobal = checkArrGlobal.concat(checkArr);
                    redisClient.lrem(redisChannels.sendOffersList, 0, offerJson, function (err, data) {
                        setPrizeStatus(game, 1);
                        sendProcceed = false;
                    });
                    console.tag('SteamBot', 'SendPrize').log('TradeOffer #' + response.tradeofferid + ' send!');
                    AcceptMobileOffer();
                });
            } else {
                console.tag('SteamBot', 'SendPrize').log('Items not found!');
                redisClient.lrem(redisChannels.sendOffersList, 0, offerJson, function (err, data) {
                    setPrizeStatus(game, 1);
                    sendProcceed = false;
                });
            }
        });

    } catch (ex) {
        console.tag('SteamBot').error('Error to send the bet');
        setPrizeStatus(game, 2);
        sendProcceed = false;
    }
};

var setPrizeStatus = function (game, status) {
    requestify.post('http://' + config.domain + '/api/setPrizeStatus', {
        secretKey: config.secretKey,
        game: game,
        status: status
    })
        .then(function (response) {

        }, function (response) {
            console.tag('SteamBot').log('Something wrong with set prize status. Retry...');
            setTimeout(function () {
                setPrizeStatus()
            }, 1000);
        });
}

var is_checkingOfferExists = function (tradeofferid) {
    for (var i = 0, len = checkingOffers.length; i < len; ++i) {
        var offer = checkingOffers[i];
        if (offer == tradeofferid) {
            return true;
            break;
        }
    }
    return false;
}

var checkedOffersProcceed = function (offerJson) {
    var offer = JSON.parse(offerJson);
    if (offer.success) {
        console.tag('SteamBot').log('Procceding accept: #' + offer.offerid);
        offers.acceptOffer({tradeOfferId: offer.offerid}, function (err, body) {
            if (!err) {
                redisClient.multi([
                    ["lrem", redisChannels.tradeoffersList, 0, offer.offerid],
                    ["lrem", redisChannels.usersQueue, 1, offer.steamid64],
                    ["rpush", redisChannels.betsList, offerJson],
                    ["lrem", redisChannels.checkedList, 0, offerJson]
                ])
                    .exec(function (err, replies) {
                        redisClient.lrange(redisChannels.usersQueue, 0, -1, function (err, queues) {
                            io.sockets.emit('queue', queues);
                            console.tag('SteamBot').log("New bet Accepted!");
                            checkedProcceed = false;
                        });
                    });
                AcceptMobileOffer();
            } else {
                console.tag('SteamBot').error('Error. With accept tradeoffer #' + offer.offerid);
                offers.declineOffer({tradeOfferId: offer.offerid});

                offers.getOffer({tradeOfferId: offer.offerid}, function (err, body) {
                    if (body.response.offer) {
                        var offerCheck = body.response.offer;
                        if (offerCheck.trade_offer_state == 2) {
                            checkedProcceed = false;
                            return;
                        }
                        if (offerCheck.trade_offer_state == 3) {
                            redisClient.multi([
                                ["lrem", redisChannels.tradeoffersList, 0, offer.offerid],
                                ["lrem", redisChannels.usersQueue, 1, offer.steamid64],
                                ["rpush", redisChannels.betsList, offerJson],
                                ["lrem", redisChannels.checkedList, 0, offerJson]
                            ])
                                .exec(function (err, replies) {
                                    redisClient.lrange(redisChannels.usersQueue, 0, -1, function (err, queues) {
                                        io.sockets.emit('queue', queues);
                                        checkedProcceed = false;
                                    });
                                });
                        } else {
                            redisClient.multi([
                                ["lrem", redisChannels.tradeoffersList, 0, offer.offerid],
                                ["lrem", redisChannels.usersQueue, 1, offer.steamid64],
                                ["lrem", redisChannels.checkedList, 0, offerJson]
                            ])
                                .exec(function (err, replies) {
                                    redisClient.lrange(redisChannels.usersQueue, 0, -1, function (err, queues) {
                                        io.sockets.emit('queue', queues);
                                        checkedProcceed = false;
                                    });
                                });
                        }
                    }
                })
            }
        });
    }
}

function AcceptMobileOffer() {
    // Информация для мобильных подтверждений
    var steamcommunityMobileConfirmations = new SteamcommunityMobileConfirmations(
        {
            steamid: config.bot.steamid,
            identity_secret: config.bot.identity_secret,
            device_id: device_id,
            webCookie: WebCookies,
        });

    steamcommunityMobileConfirmations.FetchConfirmations((function (err, confirmations) {
        if (err) {
            console.log(err);
            return;
        }
        console.tag('SteamBot', 'MobileTrades').log('Wait Offers: ' + confirmations.length);
        if (!confirmations.length) {
            return;
        }
        steamcommunityMobileConfirmations.AcceptConfirmation(confirmations[0], (function (err, result) {
            if (err) {
                console.log(err);
                return;
            }
            console.tag('SteamBot', 'MobileTrades').log('Accept result: ' + result);
        }).bind(this));
    }).bind(this));
}

var declineOffersProcceed = function (offerid) {
    console.tag('SteamBot').log('Procceding decline: #' + offerid);
    offers.declineOffer({tradeOfferId: offerid}, function (err, body) {
        if (!err) {
            console.tag('SteamBot').log('Offer #' + offerid + ' Decline!');
            redisClient.lrem(redisChannels.declineList, 0, offerid);
            declineProcceed = false;
        } else {
            console.tag('SteamBot').error('Error. With decline tradeoffer #' + offer.offerid)
                .tag('SteamBot').log(err);
            declineProcceed = false;
        }
    });
}

var queueProceed = function () {
    redisClient.llen(redisChannels.checkList, function (err, length) {
        if (length > 0 && !checkProcceed) {
            console.tag('SteamBot', 'Queues').info('CheckOffers:' + length);
            checkProcceed = true;
            checkOfferPrice();
        }
    });
    redisClient.llen(redisChannels.checkedList, function (err, length) {
        if (length > 0 && !checkedProcceed && WebSession) {
            console.tag('SteamBot', 'Queues').info('CheckedOffers:' + length);
            checkedProcceed = true;
            redisClient.lindex(redisChannels.checkedList, 0, function (err, offer) {
                checkedOffersProcceed(offer);
            });
        }
    });
    redisClient.llen(redisChannels.declineList, function (err, length) {
        if (length > 0 && !declineProcceed && WebSession) {
            console.tag('SteamBot', 'Queues').info('DeclineOffers:' + length);
            declineProcceed = true;
            redisClient.lindex(redisChannels.declineList, 0, function (err, offer) {
                declineOffersProcceed(offer);
            });
        }
    });
    redisClient.llen(redisChannels.betsList, function (err, length) {
        if (length > 0 && !betsProcceed && !delayForNewGame) {
            console.tag('SteamBot', 'Queues').info('Bets:' + length);
            betsProcceed = true;
            checkNewBet();
        }
    });
    redisClient.llen(redisChannels.sendOffersList, function (err, length) {
        if (length > 0 && !sendProcceed && WebSession) {
            console.tag('SteamBot', 'Queues').info('Send winner offers:' + length);
            sendProcceed = true;
            redisClient.lindex(redisChannels.sendOffersList, 0, function (err, offerJson) {
                offer = JSON.parse(offerJson);
                sendTradeOffer(offer.appId, offer.steamid, offer.accessToken, offer.items, '', offer.game, offerJson);
            });
        }
    });
    redisClient.llen(redisChannels.checkItemsList, function (err, length) {
        if (length > 0 && !parseItemsProcceed && WebSession) {
            console.tag('SteamBot', 'Queues').info('Parse items:' + length);
            parseItemsProcceed = true;
            redisClient.lindex(redisChannels.checkItemsList, 0, function (err, offerJson) {
                offer = JSON.parse(offerJson);
                parseOffer(offer, offerJson);
            });
        }
    });
}
var parseItemsProcceed = false;
var checkProcceed = false;
var checkedProcceed = false;
var declineProcceed = false;
var betsProcceed = false;
var sendProcceed = false;
var delayForNewGame = false;
setInterval(queueProceed, 1500);

module.exports.handleOffers = handleOffers;
module.exports.delayForNewGame = function (value) {
    delayForNewGame = value;
};

function getErrorCode(err, callback) {
    var errCode = 0;
    var match = err.match(/\(([^()]*)\)/);
    if (match != null && match.length == 2) errCode = match[1];
    callback(errCode);
}
