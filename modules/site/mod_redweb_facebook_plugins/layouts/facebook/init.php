<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_cart
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
extract($displayData);

$id   = '#facebook-plugin-' . $params->get('facebook_plugin', 'comments') . '-' . $moduleId;
$plugin = $params->get('facebook_plugin', 'comments')
?>
<div id="fb-root"></div>
<script>
    jQuery(document).ready(function () {
        jQuery.ajaxSetup({cache: true});
        jQuery.getScript('https://connect.facebook.net/<?php echo $params->get('facebook_language', 'en_US'); ?>/sdk.js', function () {
            FB.init({
                appId: '<?php echo $params->get('facebook_appid', '262562957268319'); ?>',
                version: 'v<?php echo $params->get('facebook_version', '2.11'); ?>',
                autoLogAppEvents: true,
                xfbml: true
            });

            resizeFbPage<?php echo $moduleId;?>();
        });
    });


    /**
     *
     */
    function resizeFbPage<?php echo $moduleId;?>() {
        var width = jQuery(window).width();

        if (width <= 450) {
            setSize(250, 400);
        }
        else if (width > 450 && width <= 767) {
            setSize(250, 300);
        }
        else if (width > 767) {
            setSize(null, 400);
        }
    }

    /**
     *
     * @param width
     * @param height
     */
    function setSize(width, height) {
        var el = jQuery('<?php echo $id;?> .fb-<?php echo $plugin; ?>');

        if (width != null) {
            jQuery(el).attr('data-width', width);
        }

        if (height != null) {
            jQuery(el).attr('data-height', height);
        }

        FB.XFBML.parse();
    }

    jQuery(window).on('resize', function () {
        if (typeof FB === 'undefined') {
            setTimeout(resizeFbPage<?php echo $moduleId;?>, 200);
        }
        else {
            resizeFbPage<?php echo $moduleId;?>();
        }
    });

</script>