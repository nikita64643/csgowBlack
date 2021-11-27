var sharedKey              = '';// your shared key from saved_response.txt after second step 

var SteamTotp = require('steam-totp');

console.log(SteamTotp.generateAuthCode(sharedKey));