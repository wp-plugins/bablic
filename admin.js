

jQuery(function($){


    var match = /\bbablic_cb=(.+?)(?:$|#|$)/.exec(window.location.href);
    if(match)
        return window.parent.parent.bablicCallback(match[1]);

    var HOST = 'https://www.bablic.com';

    var $siteId = $('#bablic_item_site_id');
    if(!$siteId.length)
        return;

    var $loggedInArea = $('#bablicLoggedIn');
    var $bablicConnected = $('#bablicConnected');
    var $bablicCreateSite = $('#createBablicSite');

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
        $loggedInArea.show();
        $bablicConnected.show();
        $bablicCreateSite.hide();

        $loggedInArea.find('button').unbind('click').click(function(e){
            e.preventDefault();
            window.open('http://www.bablic.com/editor/' + site.id);
            var siteId = site.id;
            var int = setInterval(function(){
                bablic.getSite(siteId,function(site){
                    if(site)
                        return;
                    clearInterval(int);
                    bablic.checkLogin(function(isLogged){
                        if(!isLogged)
                            return onBablicLogout();
                        $siteId.val('');
                        jQuery('#form1').submit();
                    });
                });
            },10000);
        });
    }

    function openAddSite(){
        $loggedInArea.show();
        $bablicCreateSite.show();
        $bablicConnected.hide();

        $loggedInArea.find('button').unbind('click').click(function(e){
            e.preventDefault();
            window.open('http://www.bablic.com/?autoStart=' + encodeURIComponent(location.hostname) + '&embedded=' + encodeURIComponent(window.location.href));

            var int = setInterval(function(){
                bablic.getSite(location.hostname,function(site){
                    if(!site)
                        return;
                    clearInterval(int);
                    onSite(site);
                    jQuery('#form1').submit();
                });
            },5000);
        });

    }

    function onBablicLogout(){
        isLogged = false;
        $('#bablic_login').text('Login');
        $loggedInArea.hide();
    }

    function onBablicLogin(){
        isLogged = true;
        $('#bablic_login').text('Logout');

        var siteId = $siteId.val();
        var host = window.location.hostname;

        var getByHost = function(){
            bablic.getSite(host,function(site){
                if(site){
                    onSite(site);
                    jQuery('#form1').submit();
                    return;
                }
                openAddSite();
            })
        }

        if(!siteId || siteId.length != 24)
            return getByHost();

        bablic.getSite(siteId && siteId.length == 24 ? siteId : host,function(site){
            if(site)
                return onSite(site);
            getByHost();
        });

    }







});

function bablicCallback(siteId){
    jQuery('input[name="bablic_item[site_id]"]').val(siteId);
    jQuery('#form1').submit();
}

//<?php $this->optionsDrawCheckbox( 'activate', 'Activate', '', 'color:#f00;' ); ?>