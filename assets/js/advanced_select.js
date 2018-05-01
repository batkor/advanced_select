(function ($, Drupal) {
  Drupal.behaviors.advancedSelect = {
    attach: function attach(context, settings) {
      var as_settings = settings.advanced_select;
      var as_elems = $('.advanced_select');
      as_elems.once().each(function () {
        var elem = $(this);
        var field_name = elem.data('advanced_select');
        var selecteds = getSelectedOptions(elem);
        elem.after(generateAdvancedElement(field_name, selecteds, as_settings[field_name]));
        setEvents(field_name);
      });
    }
  };

  function getSelectedOptions(elem) {
    output = [];
    elem.find('option').each(function () {
      if(typeof $(this).attr('selected') !== 'undefined'){
        output.push($(this).val())
      }
    });
    return output;
  }

  function generateAdvancedElement(field_name, selecteds, data) {

    var output = '<div id="advanced_select_' + field_name + '">';
    $.each(data, function (ind, elem) {
      console.log(ind)
      var select = ($.inArray(ind, selecteds) + 1) ? " selected" : "";
      output += '<div class="item' + select + '" data-value="' + ind + '">';
      output += '<div class="img">';
      output += '<img src="' + elem['url'] + '">';
      output += '</div>';
      output += '<div class="label">' + elem['label'];
      output += '</div>';
      output += '</div>';
    });
    output += '</div>';
    return output
  }

  function setEvents(elem_name) {
    var elem = $('#advanced_select_' + elem_name);
    elem.once().on('click', '.item', function () {
      var current = $(this);
      var option_val = current.data('value');
      var origin_select = $('[data-advanced_select="' + elem_name + '"]');
      var origin_option = origin_select.find('option[value="' + option_val + '"]');
      if (typeof origin_select.attr('multiple') === 'undefined') {
        origin_select.find('option').each(function () {
          $(this).removeAttr("selected");
        });
        elem.find('.item').each(function () {
          if (!current.hasClass('selected')) {
            $(this).removeClass("selected");
          }
        })
      }
      if (current.hasClass('selected')) {
        origin_option.removeAttr("selected");
      } else {
        origin_option.attr('selected', 'selected');
      }
      current.toggleClass('selected');
    });
  }

})(jQuery, Drupal);