

jQuery(function($){


    var match = /\bbablic_cb=(.+?)(?:$|#|$)/.exec(window.location.href);
    if(match)
        return window.parent.parent.bablicCallback(match[1]);

    var HOST = 'https://www.bablic.com';

    var $siteId = $('#bablic_item_site_id');
    if(!$siteId.length)
        return;
    var $embedded = $('#bablic_embedded');

    var isLogged = false;
//    var $activate = $('#bablic_item_activate');
//    $activate.click(activate).change(activate).change();
//    function activate() {
//        $embedded.toggle(this.checked);
//    }
//

    $('#bablic_login').click(function(e){
        e.preventDefault();
        if(isLogged)
            return bablic.logout(onBablicLogout);

        bablic.login(onBablicLogin);
        return false;
    });

    bablic.checkLogin(function(isLogged){
        if(isLogged)
            onBablicLogin();
    });

    function onSite(site){
        $siteId.val(site.id);
        $embedded.attr('src',HOST + '/console/' + site.id + '?embedded=' + encodeURIComponent(window.location.href)).show();
    }

    function openAddSite(){
        var title = $('head title').text();
        title = title.replace('Bablic Settings ‹ ','');
        $embedded.attr('src',HOST + '/new?title=' + encodeURIComponent(title) + '&embedded=' + encodeURIComponent(window.location.href)).show();
    }

    function onBablicLogout(){
        isLogged = false;
        $('#bablic_login').text('Login');
        $embedded.hide();
    }

    function onBablicLogin(){
        isLogged = true;
        $('#bablic_login').text('Logout');

        var siteId = $siteId.val();

        var host = window.location.hostname;
        bablic.getSite(siteId && siteId.length == 24 ? siteId : host,function(site){
            if(site){
                onSite(site);
                if(!siteId)
                    jQuery('#form1').submit();
                return;
            }
            openAddSite();
        });

    }







});

function bablicCallback(siteId){
    jQuery('input[name="bablic_item[site_id]"]').val(siteId);
    jQuery('#form1').submit();
}

//<?php $this->optionsDrawCheckbox( 'activate', 'Activate', '', 'color:#f00;' ); ?>