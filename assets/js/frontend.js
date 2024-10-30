/**
*
*/



(function ($, window, document) {

    $.fn.mclv = function () {
        var form = this;
        var last_change = form.data('last_change');
        var select = form.find('.variations select');


        this.clean = function () {
            form.find('.select_box').remove();

            return this;
        };

        this.generateOutput = function () {

            select.each(function () {
                var t = $(this),
                    type = $(this).data('type');

                var select_box = $('<div />', {
                    'class': 'select_box_' + type + ' select_box ' + t.attr('name')
                }).insertAfter(t);

                t.removeData('last_content');

                

                t.find('option').each(function () {
                    if ( $(this).data('value') ) {
                        var classes = 'select_option_' + type + ' select_option';
                        var value = $(this).data('value');
                        var o = $(this);
                        var desc = $(this).attr('value');

                        var option = $('<div/>', {
                            'class': classes
                        }).data('value', $(this).attr('value'))
                            .data('option', o.clone(true))
                            .appendTo(select_box)
                            .off('click')
                            .on('click', function (e) {
                                if ($(this).hasClass('selected')) {
                                    t.val('').change();
                                } else {
                                    e.preventDefault();
                                    t.val(o.val()).change();
                                }
                            });

                        var addOption = '20px';

                        function colorpicker(option, value, addOption){
                            option.append($('<span/>', {
                                'css': {
                                    'background': value,
                                    'border-radius': addOption
                                }
                            }));
                        }

                        function tooltip_(option, desc){
                            option.attr('title', desc ).tooltipster({
                                theme: 'tooltipster-borderless'
                            });
                        }


                        function wrap(option){
                            option.wrap('<div class="MDLC_wrap">');
                        }

                        function two_color_(option, value, addOption){
                            option.append($('<span/>', {
                                'css': {
                                    'background': 'linear-gradient(135deg, ' + value.substr(0, 7) + ' 51%, ' + value.substr(7, 14) + ' 51%)',
                                    'border-radius': addOption
                                }
                            }));
                        }

                        function description(option, desc, className){
                            option.parent().append('<p class="'+className+'">' +desc+ '</p>');
                        }

                        function borderRadius(option){
                            option[0].style.borderRadius = '20px';
                        }

                        function img_value(option, value){
                            option.append($('<img/>', {
                                'src': value
                            }));
                        }

                        switch(type){
                            case 'colorpicker':
                                colorpicker(option, value);
                                break;
                            case 'round_color':
                                borderRadius(option);
                                colorpicker(option, value, addOption);
                                break;
                            case 'color_desc':
                                wrap(option);
                                description(option, desc, 'desc');
                                colorpicker(option, value);
                                break;
                            case 'tooltip_color':
                                colorpicker(option, value);
                                tooltip_(option, desc);
                                break;
                            case 'two_colorpicker':
                                two_color_(option, value);
                                break;
                            case 'round_two_color':
                                borderRadius(option);
                                two_color_(option, value, addOption);
                                break;
                            case 'two_color_desc':
                                wrap(option);
                                description(option, desc, 'desc');
                                two_color_(option, value);
                                break;
                            case 'tooltip_two_color':
                                two_color_(option, value);
                                tooltip_(option, desc);
                                break;
                            case 'image':
                                img_value(option, value);
                                break;
                            case 'round_image':
                                img_value(option, value);
                                break;
                            case 'desc_image':
                                wrap(option);
                                description(option, desc, 'desc_img');
                                img_value(option, value);
                                break;
                            case 'tooltip_image':
                                img_value(option, value);
                                tooltip_(option, desc);
                                break;
                            case 'label':
                                option.append($('<span/>', {
                                'text': value
                                }));
                                break;
                        }
                    }
                });
            }).filter(function () {
                    return $(this).data('type') != 'select';
                }).hide();

            return form;
        };

        this.onSelect = function () {

            select.each(function () {
                var value = $(this).val();
                var options = $(this).next('.select_box'); // get next elem

                // else get siblings
                if( ! options.length ){
                    options = $(this).siblings('.select_box');
                }
                // reset class
                options = options.find('.select_option').removeClass('selected');

                if (value) {
                    options
                        .filter(function () {
                            return $(this).data('value') == value;
                        })
                        .addClass('selected');
                }
            });

            return form;
        };

        this.updateOptions = function () {
            var variations;

            if( (typeof mclv_arg != 'undefined') && ! mclv_arg.is_wc24 ) {

                form.find('.variations select').each(function (index, el) {

                    var s = $(this),
                        selected = s.val(),
                        attribute_options = s.data('active_options') ? s.data('active_options') : s.data('attribute_options');

                    if ( ! attribute_options ) return false;

                    if (selected != '') {
                        $.each( attribute_options, function (index, option_html) {
                            s.append(option_html);
                        });
                    } else {
                        s.data('active_options', s.find('option').get());
                    }

                });
            }

            return form;
        };

        return this
            .updateOptions()
            .clean()
            .generateOutput()
            .onSelect();
    };


    jQuery(function ($) {

        var form = $('.variations_form');
        var select = form.find('.variations select');

        $(document).on('mclv_change check_variations', form,function () {
            $(this).mclv();
        }).trigger('mclv_change');


        $(document).on('change', select, function () {
            form.data('last_change', $(this).attr('name'));
            $(this).data('last_content', $(this).siblings('.select_box').clone(true));
        });

        $(document).on('click', '.reset_variations', function () {
            select.removeData('last_content');
            form.removeData('last_change');
        });

        select.trigger('focusin');
    });

})(jQuery, window, document);