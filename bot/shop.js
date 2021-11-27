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
var redisClient, requestify;

module.exports.init = function (redis, requestifyCore) {
    redisClient = redis.createClient();
    requestify = requestifyCore;
}

var details = {
    account_name: config.shop.username,
    password: config.shop.password,
    two_factor_code: generatekey(config.shop.secret)
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
    itemsToSale: 'items.to.sale',
    itemsToGive: 'items.to.give',
    offersToCheck: 'offers.to.check'
}

function siteShopLogger(log) {
    console.tag('SiteShop').log(log);
}

function generatekey(secret) {
    code = SteamTotp.generateAuthCode(secret);
    return code;
}

steamClient.connect();
steamClient.on('connected', function () {
    steamUser.logOn(details);
});

steamClient.on('logOnResponse', function (logonResp) {
    if (logonResp.eresult === Steam.EResult.OK) {
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
                    siteShopLogger('Bot started!');
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
                siteShopLogger('WebSession Reloaded !');
            });
        });
    });
}

steamClient.on('servers', function (servers) {
    fs.writeFile('servers', JSON.stringify(servers));
});

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
                    if (config.admins.indexOf(offer.steamid_other) != -1) {
                        offers.acceptOffer({
                            tradeOfferId: offer.tradeofferid
                        }, function (error, traderesponse) {
                            if (!error) {
                                if ('undefined' != typeof traderesponse.tradeid) {
                                    offers.getItems({
                                        tradeId: traderesponse.tradeid
                                    }, function (error, recieved_items) {
                                        if (!error) {
                                            var itemsForParse = [], itemsForSale = [], i = 0;
                                            recieved_items.forEach(function (item) {
                                                itemsForParse[i++] = item.id;
                                            })
                                            offers.loadMyInventory({
                                                appId: 730,
                                                contextId: 2,
                                                language: 'russian'
                                            }, function (error, botItems) {
                                                if (!error) {
                                                    i = 0;
                                                    botItems.forEach(function (item) {
                                                        if (itemsForParse.indexOf(item.id) != -1) {
                                                            var rarity = '', type = '';
                                                            var arr = item.type.split(',');
                                                            if (arr.length == 2) rarity = arr[1].trim();
                                                            if (arr.length == 3) rarity = arr[2].trim();
                                                            if (arr.length && arr[0] == 'Нож') rarity = 'Тайное';
                                                            if (arr.length) type = arr[0];
                                                            var quality = item.market_name.match(/\(([^()]*)\)/);
                                                            if (quality != null && quality.length == 2) quality = quality[1];
                                                            itemsForSale[i++] = {
                                                                inventoryId: item.id,
                                                                classid: item.classid,
                                                                name: item.name,
                                                                market_hash_name: item.market_hash_name,
                                                                rarity: rarity,
                                                                quality: quality,
                                                                type: type
                                                            }
                                                        }
                                                    });
                                                }
                                                redisClient.rpush(redisChannels.itemsToSale, JSON.stringify(itemsForSale));
                                                return;
                                            });
                                        }
                                        return;
                                    });
                                }
                            }
                            return;
                        });
                    } else {
                        offers.declineOffer({tradeOfferId: offer.tradeofferid});
                    }
                    return;
                }
            });
        }
    });
}

steamUser.on('tradeOffers', function (number) {
    console.log('Shop offers: ' + number);
    if (number > 0) {
        handleOffers();
    }
});

var sendTradeOffer = function (offerJson) {
    var offer = JSON.parse(offerJson);
    try {
        offers.loadMyInventory({
            appId: 730,
            contextId: 2
        }, function (err, items) {
            var itemsFromMe = [];

            items.forEach(function (item) {
                if (item.id == offer.itemId) {
                    itemsFromMe[0] = {
                        appid: 730,
                        contextid: 2,
                        amount: item.amount,
                        assetid: item.id
                    };
                }
            });

            if (itemsFromMe.length > 0) {
                offers.makeOffer({
                    partnerSteamId: offer.partnerSteamId,
                    accessToken: offer.accessToken,
                    itemsFromMe: itemsFromMe,
                    itemsFromThem: [],
                    message: 'Спасибо за покупку на сайте ' + config.nameSite
                }, function (err, response) {
                    if (err) {
                        getErrorCode(err.message, function (errCode) {
                            if (errCode == 15 || errCode == 25 || err.message.indexOf('an error sending your trade offer.  Please try again later.')) {
                                redisClient.lrem(redisChannels.itemsToGive, 0, offerJson, function (err, data) {
                                    setItemStatus(offer.id, 4);
                                    sendProcceed = false;
                                });

                                sendProcceed = false;
                            }
                            sendProcceed = false;
                        });
                        sendProcceed = false;
                    } else if (response) {
                        redisClient.lrem(redisChannels.itemsToGive, 0, offerJson, function (err, data) {
                            sendProcceed = false;
                            AcceptMobileOffer();
                            setItemStatus(offer.id, 3);
                            console.tag('SiteShop', 'SendItem').log('TradeOffer #' + response.tradeofferid + ' send!');
                            redisClient.rpush(redisChannels.offersToCheck, response.tradeofferid);
                        });
                    }
                });
            } else {
                console.tag('SiteShop', 'SendItem').log('Items not found!');
                setItemStatus(offer.id, 2);
                redisClient.lrem(redisChannels.itemsToGive, 0, offerJson, function (err, data) {
                    sendProcceed = false;
                });
            }
        });
    } catch (ex) {
        console.tag('SiteShop').error('Error to send the item');
        sendProcceed = false;
    }
};

function AcceptMobileOffer() {
    // Информация для мобильных подтверждений
    var steamcommunityMobileConfirmations = new SteamcommunityMobileConfirmations(
        {
            steamid: config.shop.steamid,
            identity_secret: config.shop.identity_secret,
            device_id: device_id,
            webCookie: WebCookies,
        });

    steamcommunityMobileConfirmations.FetchConfirmations((function (err, confirmations) {
        if (err) {
            console.log(err);
            return;
        }
        console.tag('SiteShop', 'MobileTrades').log('Wait Offers: ' + confirmations.length);
        if (!confirmations.length) {
            return;
        }
        steamcommunityMobileConfirmations.AcceptConfirmation(confirmations[0], (function (err, result) {
            if (err) {
                console.log(err);
                return;
            }
            console.tag('SiteShop', 'MobileTrades').log('Accept result: ' + result);
        }).bind(this));
    }).bind(this));
}

var setItemStatus = function (item, status) {
    requestify.post('http://' + config.domain + '/api/shop/setItemStatus', {
        secretKey: config.secretKey,
        id: item,
        status: status
    })
        .then(function (response) {
        }, function (response) {
            console.tag('SiteShop').error('Something wrong with setItemStatus. Retry...');
            setTimeout(function () {
                setItemStatus()
            }, 1000);
        });
}

var addNewItems = function () {
    requestify.post('http://' + config.domain + '/api/shop/newItems', {
        secretKey: config.secretKey
    })
        .then(function (response) {
            var answer = JSON.parse(response.body);
            if (answer.success) {
                console.tag('SiteShop').error('Item added to site !');
                itemsToSaleProcced = false;
            }
            else {
                console.tag('SiteShop').error(answer);
            }
        }, function (response) {
            console.tag('SiteShop').error('Something wrong with newItems. Retry...');
            setTimeout(function () {
                addNewItems()
            }, 1000);
        });
}

var checkOfferForExpired = function (offer) {
    offers.getOffer({tradeOfferId: offer}, function (err, body) {
        if (body.response.offer) {
            var offerCheck = body.response.offer;
            if (offerCheck.trade_offer_state == 2) {
                var timeCheck = Math.floor(Date.now() / 1000) - offerCheck.time_created;
                console.log(timeCheck);
                if (timeCheck >= config.shop.timeForCancelOffer) {
                    offers.cancelOffer({tradeOfferId: offer}, function (err, response) {
                        if (!err) {
                            redisClient.lrem(redisChannels.offersToCheck, 0, offer, function (err, data) {
                                siteShopLogger('Offer #' + offer + ' was expired!')
                                checkProcceed = false;
                            });
                        } else {
                            checkProcceed = false;
                        }
                    });
                } else {
                    checkProcceed = false;
                }
                return;
            } else if (offerCheck.trade_offer_state == 3 || offerCheck.trade_offer_state == 7) {
                redisClient.lrem(redisChannels.offersToCheck, 0, offer, function (err, data) {
                    checkProcceed = false;
                });
            } else {
                checkProcceed = false;
            }
        } else {
            checkProcceed = false;
        }
    })
}

var queueProceed = function () {
    redisClient.llen(redisChannels.itemsToSale, function (err, length) {
        if (length > 0 && !itemsToSaleProcced) {
            console.tag('SiteShop', 'Queues').info('New items to sale:' + length);
            itemsToSaleProcced = true;
            addNewItems();
        }
    });
    redisClient.llen(redisChannels.itemsToGive, function (err, length) {
        if (length > 0 && !sendProcceed && WebSession) {
            console.tag('SiteShop', 'Queues').info('Send items:' + length);
            sendProcceed = true;
            redisClient.lindex(redisChannels.itemsToGive, 0, function (err, offerJson) {
                sendTradeOffer(offerJson);
            });
        }
    });
    redisClient.llen(redisChannels.offersToCheck, function (err, length) {
        if (length > 0 && !checkProcceed && WebSession) {
            console.tag('SiteShop', 'Queues').info('Check Offers:' + length);
            checkProcceed = true;
            redisClient.lindex(redisChannels.offersToCheck, 0, function (err, offer) {
                setTimeout(function () {
                    checkOfferForExpired(offer)
                }, 1000 * config.shop.timeForCancelOffer);
            });
        }
    });
}

var itemsToSaleProcced = false;
var sendProcceed = false;
var checkProcceed = false;
setInterval(queueProceed, 1500);

function getErrorCode(err, callback) {
    var errCode = 0;
    var match = err.match(/\(([^()]*)\)/);
    if (match != null && match.length == 2) errCode = match[1];
    callback(errCode);
}
