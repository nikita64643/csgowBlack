$(document).on('click', '#checkHash', function () {
    var hash = $('#roundHash').val().trim();
    var random = $('#roundRandom').val().trim() || '';
    var totalbank = $('#totalbank').val().trim() || 0;

    var result = $('#checkResult');
    console.log(hex_md5(random))

    if (hex_md5(random) == hash) {
        var n = Math.floor( random * parseFloat(totalbank) );
        var text = 'Хэш соответствует Числу раунда. Победный билет: ' + n;
        result.html(text);
    }
    else {
        var text = 'Хэш не соответствует Числу раунда';
        result.html(text);
    }
});