/*!
 * jQuery Character Counter v.1.0.0
 * http://github.com/kemal-bay
 * -.- . -- .- .-.. -... .- -.--
 *
 * Copyright (c) 2015 Kemal Bay
 * Licensed under GPLv2 or later.
 * http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

(function ($) {
    "use strict";

    $.fn.charCounter = function (options) {
        if (typeof String.prototype.format == "undefined") {
            String.prototype.format = function () {
                var content = this;
                for (var i = 0; i < arguments.length; i++) {
                    var replacement = '{' + i + '}';
                    content = content.replace(replacement, arguments[i]);
                }
                return content;
            };
        }

        var options = $.extend({
            backgroundColor: "#FFFFFF",
            position: {
                right: 10,
                top: 10
            },
            font:   {
                size: 10,
                color: "#a59c8c"
            },
            limit: $(this).attr('maxlength')
        }, options);

        return this.each(function () {
        	var limit = $(this).attr('maxlength');
        	
            var el = $(this),
                wrapper = $("<div/>").addClass('focus-textarea').css({
                    "position": "relative",
                        "display": "block"
                }),
                label = $("<span/>").css({
                    "zIndex": 999,
                        "backgroundColor": options.backgroundColor,
                        "position": "absolute",
                        "font-size": options.font.size,
                        "color": options.font.color
                }).css(options.position);
            
            if(options.limit > 0){
                label.text("{0}/{1}".format(el.val().length, limit));
                el.prop("maxlength", limit);
            }else{
                label.text(el.val().length);
            }

            el.wrap(wrapper);
            el.before(label);
            el.on("keyup", updateLabel)
                .on("keypress", updateLabel)
                .on('keydown', updateLabel);

            function updateLabel(e) {
                if(limit > 0){
                    label.text("{0}/{1}".format($(this).val().length, limit));
                }else{
                    label.text($(this).val().length);
                }
            }
        });
    }
})(jQuery);