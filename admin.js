

jQuery(function($){


    var match = /\bbablic_cb=(.+?)(?:$|#|$)/.exec(window.location.href);
    if(match)
        return window.parent.parent.bablicCallback(match[1]);

    var HOST = 'https://www.bablic.com';

    var $siteId = $('#bablic_item_site_id');
    if(!$siteId.length)
        return;

	var existingSite = $siteId.val();

    var $orig = $('#bablic_item_orig');
    var $target = $('#bablic_item_locales\\[0\\]');
    var $loggedInArea = $('#bablicLoggedIn');
    var $bablicConnected = $('#bablicConnected');
    var $bablicCreateSite = $('#createBablicSite');
    var $dontPermalink = $('#bablic_dont_permalink');
    var isLogged = !!existingSite;
//    var $activate = $('#bablic_item_activate');
//    $activate.click(activate).change(activate).change();
//    function activate() {
//        $embedded.toggle(this.checked);
//    }
//

    $('#bablic_login').click(function(e){
        e.preventDefault();
        //if(isLogged)
        //    return bablic.logout(onBablicLogout);
		if(existingSite)
			openExistingSite(existingSite);
		else
			openNewSite();
//        bablic.login(onBablicLogin);
        return false;
    });

	function openNewSite(){
		window.open('https://www.bablic.com/?utm_source=Wordpress&utm_medium=Plugin&utm_campaign=WPplugin&autoStart=' + encodeURIComponent(location.hostname) + '&embedded=' + encodeURIComponent(window.location.href));
		var int = setInterval(function(){
			bablic.getSite(location.hostname,function(site){
				if(!site)
					return;
				clearInterval(int);
				onSite(site);
				jQuery('#form1').submit();
			});
		},5000);
	}
	
	function openExistingSite(siteId){
		window.open('http://www.bablic.com/editor/' + siteId + '?utm_source=Wordpress&utm_medium=Plugin&utm_campaign=WPplugin');
		var int = setInterval(function(){
			bablic.getSite(siteId,function(site){
				if(site)
					return updateSiteValues(site);
				clearInterval(int);
				bablic.checkLogin(function(isLogged){
					if(!isLogged)
						return onBablicLogout();
					$siteId.val('');
					jQuery('#form1').submit();
				});
			});
		},10000);
	}

    $dontPermalink.change(function(e){
        $('#bablic_dont_permalink_hidden').val($dontPermalink.is(':checked') ? '' : '1');
        jQuery('#form1').submit();
    });
    bablic.checkLogin(function(isLogged){
        if(isLogged)
            onBablicLogin();
    });

    function updateSiteValues(site){
        var changed = false;
        if($orig.val() != site.original_locale){
            $orig.val(site.original_locale);
            changed = true;
        }
        var length = site.locales.length;
        site.locales.forEach(function(locale,i){
            var $elm = $('#bablic_item_locales\\[' + i + '\\]');
            if(!$elm.length)
                $elm = $target.clone().attr('name',$target.attr('name').replace('[0]','[' + i + ']')).attr('id',$target.attr('id').replace('[0]','[' + i + ']')).insertAfter($target);
            if($elm.val() != locale.locale)
                changed = true;
            $elm.val(locale.locale);
        });
        $target.siblings().each(function(){
            var match = /\[(\d+)\]/.exec($(this).attr('id'));
            if(!match)
                return;
            var i = Number(match[1]);
            if(i >= length)
                $(this).remove();
        });
        if(changed)
            jQuery('#form1').submit();
    }

    function onSite(site){
        $siteId.val(site.id);
        //$loggedInArea.show();
        //$bablicConnected.show();
        //$bablicCreateSite.hide();
        updateSiteValues(site);
/*        $loggedInArea.find('button').unbind('click').click(function(e){
            e.preventDefault();
            window.open('http://www.bablic.com/editor/' + site.id);
            var siteId = site.id;
            var int = setInterval(function(){
                bablic.getSite(siteId,function(site){
                    if(site)
                        return updateSiteValues(site);
                    clearInterval(int);
                    bablic.checkLogin(function(isLogged){
                        if(!isLogged)
                            return onBablicLogout();
                        $siteId.val('');
                        jQuery('#form1').submit();
                    });
                });
            },10000);
        });*/
    }

    function openAddSite(){
       // $loggedInArea.show();
        //$bablicCreateSite.show();
        //$bablicConnected.hide();

        //$loggedInArea.find('button').unbind('click').click(function(e){
        //    e.preventDefault();
            
        //});

    }

    function onBablicLogout(){
        isLogged = false;
        //$('#bablic_login').text('Login');
        //$loggedInArea.hide();
    }

    function onBablicLogin(){
        isLogged = true;
        //$('#bablic_login').text('Logout');

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

        bablic.getSite(siteId,function(site){
            if(site)
                return onSite(site);
            // clear siteId
            $siteId.val('');
            jQuery('#form1').submit();
        });

    }







});

function bablicCallback(siteId){
    jQuery('input[name="bablic_item[site_id]"]').val(siteId);
    jQuery('#form1').submit();
}

//<?php $this->optionsDrawCheckbox( 'activate', 'Activate', '', 'color:#f00;' ); ?>