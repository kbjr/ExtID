<?php

/*
| -------------------------------------------------------------------
| The ExtID configuration file
| -------------------------------------------------------------------
| Each key in the main $config array is a new configuration scheme
| that can be selected by the application. If no scheme is specified
| on run-time, the 'default' scheme will be used.
|
| -------------------------------------------------------------------
| Please Note
| -------------------------------------------------------------------
|
| The ExtID library requires certain configuration. If you intend on
| using this library, please set the following config settings in
| your ./system/application/config/config.php file. 
|
|   $config['uri_protocol'] = "PATH_INFO";
|   $config['enable_query_strings'] = TRUE;
|
| This is because providers send data back to your application using
| GET querys, which are disabled by default in CodeIgniter.
|
| -------------------------------------------------------------------
| The available configurable options are as follows:
| -------------------------------------------------------------------
|
|  ['providers']  Which providers should be listed as available for log in.
|                 Custom providers should not be listed here.
|  ['classname']  A classname to apply to all of the list elements of the
|                 generated login markup.
|  ['list_id']    The ID to apply to the <ul> element.
|  ['route']      The route that does the authentication.
|  ['resources']  The route that handles image loading.
|  ['force_link'] Always use anchors, even for forms.
|  ['callback']   The final callback URI.
|  ['google']     Customized overrides for the Google option.
|  ['facebook']   Customized overrides for the Facebook option.
|  ['twitter']    Customized overrides for the Twitter option.
|  ['myspace']    Customized overrides for the MySpace option.
|  ['yahoo']      Customized overrides for the Yahoo option.
|  ['aol']        Customized overrides for the AOL option.
|  ['blogger']    Customized overrides for the Blogger option.
|  ['customs']    An array of custom provider types.
*/

$config['default']['providers']  = array('google', 'yahoo', 'aol', 'blogger');
$config['default']['route']      = 'auth/login';
$config['default']['force_link'] = true;
$config['default']['callback']   = 'auth/callback';
$config['default']['resources']  = 'auth/resource';


/* End of file extid.php */
/* Location ./system/application/config/extid.php */
