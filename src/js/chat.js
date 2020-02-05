
/* global Cookies */
/* exported initChatJs */

function initChatJs() {

    var src = $('#produck-frame').data("src");
    $('#produck-frame').replaceWith("<iframe id='produck-frame' frameborder='0' allowfullscreen='' src='"+ src +"'></iframe>");

    setTimeout(function () {
        $('#produck-chat-block-home').not('active').find('.produck-chat-link').addClass('pulse');
        setTimeout(function () {
            $('#produck-chat-block-home').not('active').find('.produck-chat-link').removeClass('pulse');
        }, 10000);
    }, 20000);

    function initProduckPopupSettings() {
        // if popup open = active, a click refers to produck.de
        $(document).on('click', '#produck-chat-block-home', function () {
            $(this).toggleClass('active');
        });
        // closes the popup on click outside of it
        $(document).on('click', function (e) {
            var produckPopup = $('#produck-chat-block-home.active');

            if (!produckPopup.is(e.target) && produckPopup.has(e.target).length === 0) {
                produckPopup.removeClass('active');
            }
        });
    }

    initProduckPopupSettings();

    let port1 = null;
    let port2 = null;

    // Sets up a new MessageChannel
    // so we can return a Promise
    function sendCookieData() {
        return new Promise((resolve) => {
            const channel = new MessageChannel();
            port1 = channel.port1;
            port2 = channel.port2;
            // this will fire when frame will answer
            port1.onmessage = e => {
                handleMessageFromFrame(e);
                resolve(e.data);
            };
            // let frame know we're ready to get an answer
            // send it its own port
            const frame = document.getElementById('produck-frame');
            frame.contentWindow.postMessage('HereIsYourPort', '*', [port2]);
        });
    }

    function initFrameCommunication() {
        const allowedOrigins = [
              'https://produck.de',
              'https://www.produck.de',
        ];

        window.onmessage = ((e) => {
            if (e.data === 'sendPortToProduck' && allowedOrigins.includes(e.origin)) {
                sendCookieData();
            }
        });
    }

    initFrameCommunication();

    function handleMessageFromFrame(e) {
        const payload = JSON.parse(e.data);

        switch (payload.method) {
            case 'set':
                Cookies.set(payload.key, JSON.stringify(payload.data), { expires: payload.expiration });
                break;
            case 'get':
                const data = Cookies.get(payload.key);
                const returnPayload = {
                    method: 'storage#get',
                    cookieData: data,
                    exchangeId: payload.exchangeId
                };
                port1.postMessage(JSON.stringify(returnPayload));
                break;
            case 'remove':
                Cookies.remove(payload.key);
                break;
            case 'clear':
                Cookies.remove('sess_au');
                Cookies.remove('sess_re');
                Cookies.remove('produck');
                Cookies.remove('chat');
                break;
        }

        return 'Frame request accomplished';
    }
}


