jQuery(document).ready(function($) {
  $default_visibility = $('#edit-tid-mediatheque-album').parent().css("display");
  $('#edit-tid-mediatheque-album').parent().css('display','none');
  $selectedOptionVal = $('#edit-tid-mediatheque-theme').find(":selected").val();
  $selectedAlbumOptionVal = $('#edit-tid-mediatheque-album').find(":selected").val();
  ajaxRequest($selectedOptionVal,$selectedAlbumOptionVal);

  $('#edit-tid-mediatheque-theme').on('change', function (e) {
    $value = this.value;
    ajaxRequest($value,'All');
  });

  function ajaxRequest($value, $selectedAlbumOptionVal) {
    $.ajax({
      url: '/_term_generator',
      dataType: 'json',
      type: 'post',
      contentType: 'application/form-data',
      data: JSON.stringify({
        value: $value
      }),
      processData: false,
      success: function(response){
        if (response['children']) {
          if (response['children'].length === 0) {
            emptyAlbumFilter();
            $('#edit-tid-mediatheque-album').parent().css('display','none');
          }
          else {
            $('#edit-tid-mediatheque-album').parent().css('display', $default_visibility);
            feedAlbumFilter(response['children']);
            default_album_term($selectedAlbumOptionVal);
          }
        }
      },
      error: function(error){
        console.error("error ===> ", error);
      }
    });
  }

  function emptyAlbumFilter() {
    $("#edit-tid-mediatheque-album option").each(function(i){
      if ($(this).val() != 'All') {
        $("#edit-tid-mediatheque-album option[value= "+ $(this).val() +" ]").hide();
      }
    });
    $("#edit-tid-mediatheque-album").val("All");
  }

  function feedAlbumFilter(terms) {
    $terms_values = [];
    for (var i=0; i<terms.length; i++){
      $value = terms[i].tid;
      $terms_values.push($value);
    }
    $terms_values.push('All');
    $("#edit-tid-mediatheque-album option").each(function(i){
      $("#edit-tid-mediatheque-album option[value= "+$(this).val()+" ]").hide();
      if ( $terms_values.includes($(this).val())) {
        $("#edit-tid-mediatheque-album option[value= "+$(this).val()+" ]").show();
      }
    });
    $("#edit-tid-mediatheque-album").val("All");
    var str = $("#edit-tid-mediatheque-album option[value= All ]").text();
    $("#edit-tid-mediatheque-album").parent().find(".selected-option").text(str);
  }

  function default_album_term ($default_value) {
    $("#edit-tid-mediatheque-album").val($default_value);
    var str = $("#edit-tid-mediatheque-album option[value= "+ $default_value +" ]").text();
    $("#edit-tid-mediatheque-album").parent().find(".selected-option").text(str);
  }

});
