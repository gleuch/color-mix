$.fn.watch_colors = function() {
  return this.keypress(function(e) {return (e.keyCode == 13 ? false : true);}).each(function() {
    this.value = this.value.replace(/\#/ig, '').replace(/[G-Z\s\_\-\.\?]/ig, '');
    if (this.value == '') {
      $(this).parent().contents('.ex_box').css({background: 'none'});
    } else if (this.value.length == 3 || this.value.length == 6) {
      $(this).parent().contents('.ex_box').css({background: '#'+this.value});
    }

    $(this).blur(function() {
      this.value = this.value.replace(/\#/ig, '').replace(/[G-Z\s\_\-\.\?]/ig, '');
      if (this.value == '') {
        $(this).parent().contents('.ex_box').css({background: 'none'});
      } else if (this.value.length == 3 || this.value.length == 6) {
        $(this).parent().contents('.ex_box').css({background: '#'+this.value});
      }
    });
  });
};



$(document).ready(function() {
  var v = function() {$('#colors').append('<fieldset class="color"><span class="ex_box"></span><input type="text" class="inp_color" name="color[]" value="" /></fieldset>'); $('input.inp_color:last').watch_colors().each(function() {this.focus();}); return false;};
  $('#another_palette').attr('href', '#').click(v).keydown(v);
  $('input.inp_color').watch_colors();
});
