/* exported initQuackJs */

function initQuackJs() {
    //convert url in quack-oberview clickable links
    function linkifyDialogue() {

        // http://, https://, ftp://
        var urlPattern = /\b(?:https?|ftp):\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|]/gim;

        // www. sans http:// or https://
        var pseudoUrlPattern = /(^|[^\/])(www\.[\S]+(\b|$))/gim;

        // Email addresses
        var emailAddressPattern = /[\w.]+@[a-zA-Z_-]+?(?:\.[a-zA-Z]{2,6})+/gim;

        //replaces url-like-elements with a-tags
        if (!String.linkify) {
            Object.defineProperty(String.prototype, "linkify", {
                value: function () {
                    return this
                    .replace(urlPattern, '<a href ="$&" target="blank">$&</a><span>*</span>')
                    .replace(pseudoUrlPattern, '$1<a href="http://$2" target="blank">$2</a><span>*</span>')
                    .replace(emailAddressPattern, '<a href="mailto:$&" target="blank">$&</a>');
                }
            });
        }

        var textElem = $('#quacklist-wrapper .question-hyperlink');

        textElem.each(function () {
            var chatText = this.innerHTML;
            // just replace text if containing urlPattern
            if (chatText.match(urlPattern) || chatText.match(pseudoUrlPattern) || chatText.match(emailAddressPattern)) {
                var linkedText = chatText.linkify();
                $(this).html(linkedText);
            }
        });
    }

    /* global Shariff */
    /* exported styleShariff */
    /* exported initShareContent */

    function styleShareShariff(quackRef, title) {
        var buttonsContainer = $('.share-shariff');
        new Shariff(buttonsContainer, {
            orientation: 'horizontal',
            url: quackRef,
            mailUrl: "mailto:?view=mail",
            mailBody: decodeURI("Hi, ich%20habe%20gerade%20folgende%20Antwort%20gefunden,%20die%20f%C3%BCr%20dich%20von%20Interesse%20sein%20k%C3%B6nnte:%20{url}.%20Schau%20es%20dir%20mal%20an."),
            lang: "de",
            infoUrl: quackRef,
            title: title,
            services: "[facebook; twitter; instagram; googleplus; xing; linkedin; mail;]",
            mediaUrl: "assets/img/ducky.png",
            buttonStyle: "icon",
            theme: "standard",
            referrerTrack: null,
            twitterVia: null
        });
    }

    function initShareContent() {

        $(document).on('click', '#share-brand > .share', function () {
            // for quacksSite get href from current site
            let questionRefDetailSite = window.location.href;
            let questionTextDetailSite = $("#question").text();
            createShareCard(questionRefDetailSite, questionTextDetailSite);
        });

        $(document).on('click', '.views > .share', function () {
            let questionRefSingleCard =  $(this).parents('.dialogue-summary').find('h3 > a').attr('href');
            let questionTextSingleCard = $(this).parents('.dialogue-summary').find('h3 > a').text();
            createShareCard(questionRefSingleCard, questionTextSingleCard);
        });

        function createShareCard(href, question) {

            // for the future, we can provide beautiful shortlinks
            const canonicalElement = document.querySelector('link[rel=canonical]');
            if (canonicalElement !== null) {
                href = canonicalElement.href;
            }

            if (navigator.share) {
                navigator.share({
                    title: question,
                    text: 'Good Question, Best Answer',
                    url: href,
                })
                  .then(() => console.log('Successful share'))
                  .catch((error) => console.log('Error sharing', error));
            }
            else if (!navigator.share) {
                $(".share-url").val(href);
                $('#share-modal').css({ "display": "flex" });
                styleShareShariff(href, question);
                copytoClipboard(href);
                closeShareCard();
            }
        }
    }


    function copytoClipboard(inputVal) {

        $(document).on('click', '.content-copy', function () {
            this.copied = false;

            // Create textarea element
            let textarea = document.createElement('textarea');

            // Set the value of the text
            textarea.value = inputVal;

            // Make sure we cant change the text of the textarea
            textarea.setAttribute('readonly', '');

            // Hide the textarea off the screnn
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';

            // Add the textarea to the page
            document.body.appendChild(textarea);

            // Copy the value of the textarea
            textarea.select();

            try {
                var successful = document.execCommand('copy'); //jshint ignore:line
                this.copied = true;
            } catch (err) {
                this.copied = false;
            }

            textarea.remove();
        });
    }

    function closeShareCard() {
        $(document).on('click', '#close-share-modal', function () {
            $('#share-modal').css({ "display": "none" });
        });
    }

    linkifyDialogue();
    initShareContent();

}