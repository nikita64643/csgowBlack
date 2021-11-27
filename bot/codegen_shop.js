var sharedKey              = ''; 

var SteamTotp = require('steam-totp');

console.log(SteamTotp.generateAuthCode(sharedKey));