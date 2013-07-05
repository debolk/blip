$(document).ready(function(){
  $('.spec').on('click', '.header', function(){
    $('.description', $(this).parent()).toggle();
  });
});