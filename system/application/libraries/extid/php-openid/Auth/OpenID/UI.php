<?php

/**
 * An implementation of the OpenID UI Extension
 *
 * See:
 * http://svn.openid.net/repos/specifications/user_interface/1.0/trunk/openid-user-interface-extension-1_0.html
 */

require_once OPENID_DIRECTORY."Auth/OpenID/Extension.php";

define('OPENID_UI_NS_URI',
       'http://specs.openid.net/extensions/ui/1.0');

class OpenID_UI_Request extends Auth_OpenID_Extension {

    var $ns_alias = 'ui';
    var $ns_uri = OPENID_UI_NS_URI;
    var $mode = null;
    var $icon = null;
    var $lang = null;

    function setPopup()
    {
        $this->mode = 'popup';
    }

    function setIcon()
    {
        $this->icon = 'true';
    }

    function setLang( $pref_lang )
    {
        $this->lang = $pref_lang;
    }

    function getExtensionArgs()
    {
        $args = array();
	if( !is_null( $this->mode ) ){
	        $args['mode'] = $this->mode;
	}
	if( !is_null( $this->icon ) ){
        	$args['icon'] = $this->icon;
	}
	if( !is_null( $this->lang ) ){
	        $args['lang'] = $this->lang;
	}
        return $args;
    }
}


