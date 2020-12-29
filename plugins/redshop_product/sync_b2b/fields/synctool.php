<?php
/**
 * @package     \Redshopb.Site
 * @subpackage  Fields
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

/**
 * Supports an HTML grouped select list of menu item grouped by menu
 *
 * @since  1.1.0
 */
class JFormFieldSynctool extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	public $type = 'Synctool';

	/**
	 * Method to get the field input markup fora grouped list.
	 * Multiselect is enabled by using the multiple attribute.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$productUrl = 'index.php?option=com_ajax&group=redshop_product&plugin=sync_b2b_products&format=json&ignoreMessages=0'
			. '&' . JSession::getFormToken() . '=1';

		JFactory::getDocument()->addScriptDeclaration('
		jQuery(document).ready(function (){
			
			function syncProduct(){
				jQuery.ajax({
					url: \'' . $productUrl . '\',
					cache: false,
					dataType:\'json\',
				}).always(function (data, textStatus){
					var haveErrors = false;
					if (data)
					{
						jQuery.each(data.messages, function(messageTypeIdx, messageTypeData) {
							jQuery.each(messageTypeData, function(messageIdx, messageData){
								var msg = \'<div class="alert alert-info alert-\' + messageTypeIdx + \'"'
			. ' style = "white-space: normal;word-break: break-word;" > \' + messageData + \' </span ><br />\';
								jQuery(\'#syncProductsLog\').append(msg);
								if (messageData.type_message == \'important\'){
									haveErrors = true;
								}
							});
						});
					}
					if (!haveErrors && data && data.success == true && data.data[0].goToNextStep == 1){
						syncProduct();
					}else{
						initUpdateAllEvent();
					}
				});
			}
			
			function initUpdateAllEvent(){
				jQuery(\'#syncProducts\')
					.off(\'click\')
					.removeClass(\'disabled\')
					.html(\'' . JText::_('PLG_REDSHOP_PRODUCT_SYNC_B2B_SYNCTOOL_BUTTON') . '\')
					.on(\'click\', function(e){
						e.preventDefault();
						jQuery(\'#syncProductsLog\').html(\'\');
						var $this = jQuery(this);

						$this.addClass(\'disabled\')
							.off(\'click\')
							.html(\'' . JText::_('PLG_REDSHOP_PRODUCT_SYNC_B2B_SYNCTOOL_BUTTON') . '\');
						syncProduct();
					});
			}
			
			initUpdateAllEvent();
		});
		');

		return '<div class="row-fluid">
			<div class="span6">
				<p>
					<button type="button" class="btn btn-info" id="syncProducts">'
			. JText::_('PLG_REDSHOP_PRODUCT_SYNC_B2B_SYNCTOOL_BUTTON') . '</button>
				</p>
				<div id="syncProductsLog"></div>
			</div>
		</div>';
	}
}