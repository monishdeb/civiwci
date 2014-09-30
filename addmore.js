// Updated to wait until the document is loaded. addmore_link

cj(function ( $ ) { 

  $("#ProgressBar").validate({
    rules: {
      starting_amount: {
        required: true,
        number: true
      },
      progressbar_name: {
        required: true
      },
      goal_amount: {
        required: true,
        number: true
      },
      contribution_page_1: {
        required: true
      },
      percentage_1: {
        required: true,
        max: 100,
        number: true
      }
    }
  });

  $('#addmore_link').on('click', function( e ) {
    e.preventDefault();
    var count = parseInt($('input[name=contrib_count]').val());
    count++;

    var c_page_sel = $('select[name=contribution_page_1]').clone().attr('id', "contribution_page_" + count);
    c_page_sel.attr("name", "contribution_page_" + count);

    var id_section = "crm-section-con-" + count;
    var sect_tag = "<div class=\"crm-section\" id=" + id_section + "> <div class=\"label\"><label>Contribution Page</label>";
    $('#addmore_link').parent().parent().before(sect_tag);

    var id_content = "content_con-" + count;
    $('#' + id_section).append("<div class=\"content\" id="+ id_content + ">");
    $('#' + id_content).append(c_page_sel);
    $('#' + id_content).append('<a id=\"remove_link\" class=\"form-link\" href=\"remove\" name=\"remove_link-' + count + '\"> Remove</a>');
    $('#' + id_section).append("</div");

    id_section = "crm-section-per-" + count;
    sect_tag = "<div class=\"crm-section\" id=" + id_section + "> <div class=\"label\"><label>Percentage</label>";
    $('#addmore_link').parent().parent().before(sect_tag);

    id_content = "content_per-" + count;
    $('#' + id_section).append("<div class=\"content\" id="+ id_content + ">");
    $('#' + id_content).append('<input type="text" size="20" id = percentage_'+ count + ' name="percentage_' + count +'" value="" />');
    $('#' + id_section).append("</div");

    $( "#contribution_page_" + count).rules( "add", {
      required: true
    });
    
    $( "#percentage_" + count).rules( "add", {
      required: true,
      max: 100,
      number: true
    });
    
    $('input[name=contrib_count]').val(count);
    
  });

  $('#remove_link').live('click', function( e ) {
    e.preventDefault();
    
    var rem_name = e.target.name;
    //assuming that - is the delimiter. second string will be the count
    var rem_name_ar = rem_name.split('-');
    var contri_page = "\"#percentage_" + rem_name_ar[1] + "\"";

    $('#crm-section-con-'+ rem_name_ar[1] +'').remove();
    $('#crm-section-per-'+ rem_name_ar[1] +'').remove();

  });
 
});
