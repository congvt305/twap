/**
 * Created by magenest on 12/01/2019.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/storage',
    'jquery/ui',
    'bioEp',
    'slick'
], function ($, customerData, modal, urlBuilder, fullScreenLoader, storage) {
    'use strict';
    $.widget('magenest.popup', {
        options: {
            dataPopup: {}
        },
        carousel: null,
        _create: function () {
            this._showPopup();
        },
        _showPopup: function () {
            var self = this,
                popupElem = $('#magenest-popup').length,
                html_content = this.options.dataPopup.html_content,
                popup_id = this.options.dataPopup.popup_id,
                cookie_lifetime = this.options.dataPopup.cookie_lifetime,
                enable_cookie = this.options.dataPopup.enable_cookie_lifetime,
                cookies = self._getCookie('magenest_cookie_popup'),
                values = {},
                view_page = 1,
                htmlData = [],
                popupIdData = [],
                css_style = this.options.dataPopup.css_style;
            if (cookies != null && cookies != "") {

                var cookieArr = JSON.parse(cookies);
                values = cookieArr;
                if (typeof cookieArr.view_page !== 'undefined' || cookieArr.view_page != null) {
                    view_page += cookieArr.view_page;
                    cookieArr.view_page = view_page;
                    values = cookieArr;
                } else {
                    values['view_page'] = view_page;
                }
            } else {
                values['view_page'] = view_page;
            }

            self._createCookie('magenest_cookie_popup', JSON.stringify(values), cookie_lifetime);
            if (popupElem <= 1) {
                var popup_trigger = this.options.dataPopup.popup_trigger,
                    number_x = this.options.dataPopup.number_x,
                    display_popup = this.options.dataPopup.floating_button_display_popup;
                    htmlData[popup_trigger] = html_content;
                    popupIdData[popup_trigger] = popup_id;
                if (this.options.dataPopup.preview == 1 && this.options.dataPopup.preview) {
                    bioEp.init({
                        html: html_content,
                        css: this.options.dataPopup.css_style,
                        popup_id: popup_id,
                        showOnDelay: true,
                        delay: 0,
                        onPopup: function () {
                            self._createCarousel().slick("setPosition");
                        }
                    });
                } else if (popup_trigger === 1) {
                    var id = 'magenest-popup popup-bio_ep';
                    $('#magenest-popup').attr('id', id);
                    bioEp.init({
                        html: htmlData[1],
                        showOnDelay: true,
                        delay: number_x,
                        css: css_style,
                        popup_id: popupIdData[1],
                        cookie_lifetime: cookie_lifetime,
                        display_popup: display_popup,
                        onPopup: function () {
                            self._createCarousel().slick("setPosition")
                        }
                    });

                } else if (popup_trigger === 2) {
                    var id = 'magenest-popup popup-bio_ep';
                    $('#magenest-popup').attr('id', id);
                    self._scrollToShow();
                } else if (popup_trigger === 3) {
                    var id = 'magenest-popup popup-bio_ep';
                    $('#magenest-popup').attr('id', id);
                    if (view_page >= number_x) {
                        bioEp.init({
                            html: htmlData[3],
                            css: this.options.dataPopup.css_style,
                            popup_id: popupIdData[3],
                            cookie_lifetime: cookie_lifetime,
                            showOnDelay: true,
                            delay: 0,
                            onPopup: function () {
                                self._createCarousel().slick("setPosition");
                                self._eraseCookie('magenest_cookie_popup');
                            }
                        });
                    }
                } else if (popup_trigger === 4) {
                    var id = 'magenest-popup popup-bio_ep';
                    $('#magenest-popup').attr('id', id);
                    bioEp.init({
                        html: htmlData[4],
                        css: this.options.dataPopup.css_style,
                        popup_id: popupIdData[4],
                        cookie_lifetime: cookie_lifetime,
                        onPopup: function () {
                            self._createCarousel().slick("setPosition")
                        }
                    });
                    $(document).off('mouseleave');
                } else if (popup_trigger === 5 && self._isMobile()) {//Back button
                    bioEp.init({
                        html: html_content,
                        css: css_style,
                        popup_id: popup_id,
                        showOnDelay: true,
                        delay: 0,
                        display_popup: 1,// avoid show popup immediately
                        onPopup: function () {
                            self._createCarousel().slick("setPosition");
                        }
                    });
                    $(window).on('popstate', function(event) {
                        if (window.location.href.indexOf('#') == -1) {
                            bioEp.init({ display_popup: 0});
                        }
                    });
                } else if (popup_trigger === 6) {
                    var id = 'magenest-popup popup-bio_ep';
                    $('#magenest-popup').attr('id', id);
                    bioEp.init({
                        html: htmlData[6],
                        showOnDelay: true,
                        delay: number_x,
                        css: css_style,
                        popup_id: popupIdData[6],
                        cookie_lifetime: cookie_lifetime,
                        display_popup: display_popup,
                        is_idle_popup: true,
                        onPopup: function () {
                            self._createCarousel().slick("setPosition")
                        }
                    });
                }else if (popup_trigger === 7 && !self._isMobile()) { //switch tab
                    document.addEventListener("visibilitychange", () => {
                        if (document.visibilityState === 'visible') {
                            var id = 'magenest-popup popup-bio_ep';
                            $('#magenest-popup').attr('id', id);
                            bioEp.init({
                                html: htmlData[7],
                                showOnDelay: true,
                                delay: 0,
                                css: css_style,
                                popup_id: popupIdData[7],
                                cookie_lifetime: cookie_lifetime,
                                shown:false,
                                display_popup: display_popup,
                                onPopup: function () {
                                    self._createCarousel().slick("setPosition")
                                }
                            });
                        }
                    });
                }
            }
            $('#popup-action-form').submit(function (e){
                e.preventDefault();
            });
            self._clickSuccess();
            self._addClassDefault();
            self._addPositionInPage();
            self._addAnimation();
            self._closeVideo();
        },
        // Create a cookie
        _createCookie: function (name, value, days, sessionOnly) {
            var expires = "";
            if (sessionOnly) {
                expires = "; expires=0"
            } else if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 1000));
                expires = "; expires=" + date.toGMTString();
            }
            document.cookie = name + "=" + value + expires + "; path=/";
        },
        // Get the value of a cookie
        _getCookie: function (name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(";");
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == " ") {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        },
        // Delete a cookie
        _eraseCookie: function (name) {
            this._createCookie(name, "", -1);
        },
        /**
         * Scroll to show popup
         * @private
         */
        _scrollToShow: function () {
            var self = this;
            $(window).scroll(function () {
                var scrollTop = $(window).scrollTop(),
                    docHeight = $(document).height(),
                    winHeight = $(window).height(),
                    scrollPercent = (scrollTop) / (docHeight - winHeight),
                    optionScroll = (self.options.dataPopup.number_x * 1) / 100,
                    popup_id = self.options.dataPopup.popup_id,
                    html_content = self.options.dataPopup.html_content,
                    cookie_lifetime = self.options.dataPopup.cookie_lifetime;
                if (scrollPercent >= optionScroll) {
                    bioEp.init({
                        html: html_content,
                        showOnDelay: true,
                        delay: 0,
                        css: self.options.dataPopup.css_style,
                        popup_id: popup_id,
                        cookie_lifetime: cookie_lifetime,
                        onPopup: function () {
                            self._createCarousel().slick("setPosition")
                        }
                    });
                    $(window).off('scroll');
                    self._addClassDefault();
                    self._addPositionInPage();
                    self._addAnimation();
                    self._clickSuccess();
                    self._closeVideo();
                }
            });


        },
        /**
         * Event click submit button
         * @private
         */
        _clickSuccess: function () {
            var self = this,
                coupon = self.options.dataPopup.coupon_code,
                thankyou = self.options.dataPopup.thankyou_message,
                popup_type = self.options.dataPopup.popup_type,
                popup_id = self.options.dataPopup.popup_id;

            $('#popup-submit-button').click(function () {
                // use #popup-action-form as parent when get email field to prevent get incorrect value
                // in case multiple form use the same input name='email' field (ie: default subscribe form)
                var newsletter = $('#popup-action-form input[name=email]').val();
                $('#popup-submit-button strong').hide();
                $('#popup-submit-button i').hide();
                $('#popup-submit-button').append('<i class="fa fa-spinner fa-spin center" aria-hidden="true"></i>');
                $('#popup-action-form').validation();
                if (newsletter === '') {
                    if($('#popup-action-form').validation('isValid') === false){
                        if($('#popup-action-form input[type=radio]').hasClass('mage-error')){
                            $('#popup-action-form input[type=radio]').parent().append('<p style="color:red" id="radio-type-mage-error">Require field!</p>');
                        }else{
                            $('#radio-type-mage-error').remove();
                        }
                        $('#popup-submit-button .fa-spinner').remove();
                        $('#popup-submit-button strong').show();
                        $('#popup-submit-button i').show();
                    }else{
                        $('#radio-type-mage-error').remove();
                    }
                } else {
                    if($('#popup-action-form').validation('isValid') === false){
                        if($('#popup-action-form input[type=radio]').hasClass('mage-error')){
                            $('#popup-action-form input[type=radio]').parent().append('<p style="color:red" id="radio-type-mage-error">Require field!</p>');
                        }else{
                            $('#radio-type-mage-error').remove();
                        }
                        $('#popup-submit-button .fa-spinner').remove();
                        $('#popup-submit-button strong').show();
                        $('#popup-submit-button i').show();
                    }else{
                        $('#radio-type-mage-error').remove();
                        var url = urlBuilder.build('magenest_popup/popup/subscriber');
                        var data = [],
                            payload,
                            popupObj = {};
                        popupObj.name = popup_id;
                        popupObj.value = popup_type;
                        data.push(popupObj);
                        $('.popup-newsletter').each(function (i) {
                            var obj = {};
                            obj.name = $(this).attr('name');
                            // Fix ajax send incorrect selected radio input value
                            if ($(this).is(':radio')) {
                                obj.value = jQuery('input.popup-newsletter[name="' + jQuery(this).attr('name') + '"]:checked').val();
                            } else {
                                obj.value = $(this).val();
                            }
                            data.push(obj);
                        });
                        $('.bld-table-cont input').each(function (i) {
                            var obj = {};
                            obj.name = $(this).attr('name');
                            obj.value = $(this).val();
                            data.push(obj);
                        });
                        payload = {
                            dataPopup: data
                        };
                        storage.post(
                            url,
                            JSON.stringify(payload)
                        ).done(
                            function (response) {
                                var obj_response = JSON.parse(response);
                                if (obj_response.status == 0) {
                                    $('.popup-step-1 .popup-content .popup-action .popup-message').remove();
                                    $('.popup-step-1 .popup-content .popup-action').append(function () {
                                        return '<div class="popup-message" style="color: red;"><span>' + obj_response.message + '</span></span></div>';
                                    });
                                    $('#popup-submit-button .fa-spinner').remove();
                                    $('#popup-submit-button strong').show();
                                    $('#popup-submit-button i').show();
                                } else {
                                    $('.popup-step-1').hide();
                                    $('.popup-step-1').after('<div class="popup-step-2"></div>');
                                    $('.popup-step-2').prepend('<div class="popup-content"></div>');
                                    if (coupon) {
                                        $('.popup-step-2 .popup-content').prepend(function () {
                                            return '<div class="popup-coupon"><span> Your coupon code is <span class="coupon"> ' + coupon + '</span></span></div>';
                                        });
                                    }
                                    if (thankyou) {
                                        $('.popup-step-2 .popup-content').append(function () {
                                            return '<div class="popup-thankyou"><span>' + thankyou + '</span></div>';
                                        });
                                    } else {
                                        $('.popup-step-2 .popup-content').append(function () {
                                            return '<div class="popup-thankyou"><span>Thank you</span></div>';
                                        });
                                    }
                                }
                            }
                        ).fail(
                            function (response) {
                                console.log(response);
                            }
                        );
                    }
                }
            })
        },

        /**
         * Event add class template default
         * @private
         */
        _addClassDefault: function () {
            var self = this,
                popup_template_id = self.options.dataPopup.popup_template_id,
                template_id = self.options.dataPopup.template_id,
                class_default = self.options.dataPopup.class;
            $('.cursor-pointer').click(function () {
                $('.popup-box-1').hide();
                $('.popup-box-2').show();
            });

            if (popup_template_id || typeof template_id != 'undefined') {
                $('#bio_ep').addClass(class_default);
                if (class_default == 'popup-default-3') {
                    $('#bio_ep_bg').addClass('popup-bg-3');
                } else {

                }
            } else {
                $('#bio_ep').addClass('popup-default-1');
            }
        },

        _isMobile: function () {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },
        /**
         * Event add postion in page for popup
         * @private
         */
        _addPositionInPage: function () {
            var self = this,
                positioninpage = self.options.dataPopup.popup_positioninpage;
            //== [ general add class ]
            $('#bio_ep_bg').addClass('popup-bg-none');

            if (positioninpage == 1) {
                $('#bio_ep').addClass('popup-top-left');
            } else if (positioninpage == 2) {
                $('#bio_ep').addClass('popup-top-right');
            } else if (positioninpage == 3) {
                $('#bio_ep').addClass('popup-bottom-left');
            } else if (positioninpage == 4) {
                $('#bio_ep').addClass('popup-bottom-right');
            } else if (positioninpage == 5) {
                $('#bio_ep').addClass('popup-middle-left');
            } else if (positioninpage == 6) {
                $('#bio_ep').addClass('popup-middle-right');
            } else if (positioninpage == 7) {
                $('#bio_ep').addClass('popup-top-center');
            } else if (positioninpage == 8) {
                $('#bio_ep').addClass('popup-bottom-center');
            } else if (positioninpage == 0) {
                $('#bio_ep').addClass('popup-center-center');
            }
        },

        /**
         * Event add animation for popup
         * @private
         */
        _addAnimation: function () {
            var self = this,
                animation = self.options.dataPopup.popup_animation,
                positioninpage = self.options.dataPopup.popup_positioninpage;

            if (animation == 1) {
                $('#bio_ep .magenest-popup-inner').addClass('animate__zoomIn');
            } else if (animation == 2) {
                //== [ Fix animation transform ]
                $('#bio_ep .magenest-popup-inner').addClass('animate__zoomOut');
            } else if (animation == 3) {
                $('#bio_ep .magenest-popup-inner').addClass('animate__bounceInLeft');
            } else if (animation == 4) {
                $('#bio_ep .magenest-popup-inner').addClass('animate__bounceInRight');
            } else if (animation == 5) {
                $('#bio_ep .magenest-popup-inner').addClass('animate__bounceInDown');
            } else if (animation == 6) {
                $('#bio_ep .magenest-popup-inner').addClass('animate__bounceInUp');
            }
        },

        /**
         * Turn off the video if the popup's has it
         * @private
         */
        _closeVideo: function () {
            $('#bio_ep_close').click(function (e) {
                e.preventDefault();
                $('.popup-video').children('iframe').attr('src', '');
            });
        },
        _createCarousel: function () {
            return $('.popup-content-wrapper .popup-product-hotdeals:not(.only-one-product) .widget-product-grid').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                arrows: true,
                dots: false,
                responsive: [
                    {
                        breakpoint: 575,
                        settings: {
                            arrows: true,
                            slidesToShow: 1
                        }
                    },
                    {
                        breakpoint: 767,
                        settings: {
                            arrows: true,
                            slidesToShow: 2
                        }
                    },
                    {
                        breakpoint: 1199,
                        settings: {
                            slidesToShow: 2
                        }
                    }
                ]
            }),
                $('.popup-content-wrapper .popup-product-hotdeals.only-one-product .widget-product-grid').slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: false,
                    responsive: [
                        {
                            breakpoint: 575,
                            settings: {
                                slidesToShow: 1
                            }
                        },
                        {
                            breakpoint: 767,
                            settings: {
                                slidesToShow: 1
                            }
                        },
                        {
                            breakpoint: 1199,
                            settings: {
                                slidesToShow: 1
                            }
                        }
                    ]
                });
        }
    });
    return $.magenest.popup;
});
