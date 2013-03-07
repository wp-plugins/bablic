

console.log('admin bablic');

jQuery(function($){

    var match = /\bbablic_cb=(.+?)(?:$|#|$)/.exec(window.location.href);
    if(match){
        window.parent.parent.bablicCallback(match[1]);
    }

    var $siteId = $('#bablic_item_site_id');

    var $activate = $('#bablic_item_activate');

    var $embedded = $('#bablic_embedded');

    $siteId.change(function(){
        var value = $(this).val() || '';
        $embedded.attr('src','http://www.bablic.com/console/' + (value.length!=24 ? 'new' : value) + '?embedded=' + encodeURIComponent(window.location.href));

        if(this.checked && $siteId.val().length == 24 != isActivated)
            $submit.show();
        else
            $submit.hide();
    });

    var isActivated = $activate.is(':checked') && $siteId.val().length == 24;

    var $submit = $('p.submit').hide();


    var activate = function(){
        if(this.checked)
            $embedded.show();
        else
            $embedded.hide();
        if((this.checked && $siteId.val().length == 24) != isActivated)
            $submit.show();
        else
            $submit.hide();
    };

    $siteId.change();

    $activate.click(activate).change(activate);



});

function bablicCallback(siteId){
    jQuery('input[name="bablic_item[site_id]"]').val(siteId);
    jQuery('#form1').submit();
}