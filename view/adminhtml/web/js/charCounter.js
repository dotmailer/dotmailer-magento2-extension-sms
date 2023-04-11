require([
    'jquery',
    'Dotdigitalgroup_Sms/js/counter/smsCounter'
], function ($) {
    'use strict';

    let selectors = [],
     unicodeMessageSelector = '#ddg-unicode';

    /**
     * @param {String} counterSelector
     * @param {String} commentSelector
     * @param {String} totalSelector
     * @param {String} smsText
     */
    function updateNote(counterSelector, commentSelector, totalSelector, smsText) {
        // eslint-disable-next-line no-undef
        let smsParsed = SmsCounter.count(smsText);

        $(counterSelector).text(smsParsed.length);
        $(totalSelector).text(smsParsed.messages);

        if (smsText.match(/{{([^}]+)\}}/si)) {
            $(commentSelector).show();
        } else {
            $(commentSelector).hide();
        }
    }

    /**
     * Search for unicode in all selectors
     */
    function searchForUnicode() {
        let unicodeFound = false;

        selectors.forEach(function (entry) {
            if ($(entry).val() !== undefined) {
                // eslint-disable-next-line no-undef
                let smsParsed = SmsCounter.count($(entry).val());

                if (smsParsed.encoding === 'UTF16') {
                    unicodeFound = true;
                    $(unicodeMessageSelector).show();
                }
            }

            if (!unicodeFound) {
                $(unicodeMessageSelector).hide();
            }
        });
    }

    /**
     * @param {String} smsText
     */
    function updateUnicode(smsText) {
        // eslint-disable-next-line no-undef
        let smsParsed = SmsCounter.count(smsText);

        if (smsParsed.encoding === 'UTF16') {
            $(unicodeMessageSelector).show();
        } else {
            searchForUnicode();
        }
    }

    /**
     * @param {Object} callback
     * @param {Number} ms
     */
    function delay(callback, ms) {
        let timer = 0;

        return function () {
            // eslint-disable-next-line consistent-this
            let context = this,
                args = arguments;

            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, ms || 0);
        };
    }

    $(document).ready(function () {
        $('.ddg-note').each(function (i, obj) {
            selectors.push('#' + obj.firstElementChild.id.replace('_counter', ''));
        });

        searchForUnicode();

        selectors.forEach(function (entry) {
            let counterSelector = entry + '_counter',
             commentSelector = entry + '_comment',
             totalSelector = entry + '_total';

            if ($(entry).val() !== undefined) {
                updateNote(counterSelector, commentSelector, totalSelector, $(entry).val());
            }
        });
    });

    // eslint-disable-next-line no-use-before-define
    $(document).on('keyup', selectors.join(', '), delay(function (event) {
        const message = $(event.target).val();
        let counterSelector = '#' + event.target.id + '_counter',
         commentSelector = '#' + event.target.id + '_comment',
         totalSelector = '#' + event.target.id + '_total';

        if (message !== undefined) {
            // eslint-disable-next-line no-use-before-define
            updateNote(counterSelector, commentSelector, totalSelector, message);
            // eslint-disable-next-line no-use-before-define
            updateUnicode(message);
        }
    }, 500));
});

