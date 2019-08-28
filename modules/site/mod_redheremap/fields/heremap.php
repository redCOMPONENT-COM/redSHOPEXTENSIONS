<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldHeremap extends JFormField {
	
	protected $type = 'heremap';

	// getLabel() left out

	public function getInput() 
	{
		$this->class="testsetseetset";
		$document = JFactory::getDocument();
		$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-core.js');
		$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-service.js');
		$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-ui.js');
		$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-mapevents.js');
		$document->addScript(JUri::root() . 'modules/mod_redheremap/assets/redheremap.js');
		$document->addStyleSheet('https://js.api.here.com/v3/3.0/mapsjs-ui.css');
		$document->addStyleSheet(JUri::root() . 'modules/mod_redheremap/assets/app.css');

		$appId = $this->form->getValue('api', 'params');
		$appCode = $this->form->getValue('code', 'params');
		$zoom = $this->form->getValue('zoom', 'params');
		$lat = $this->form->getValue('lat', 'params');
		$lng = $this->form->getValue('lng', 'params');
		$tiletype = $this->form->getValue('tiletype', 'params');
		$scheme = $this->form->getValue('scheme', 'params');
		$info = $this->form->getValue('info', 'params');
		$info = trim(preg_replace('/\s+/', ' ', $info));
		$icon = $this->form->getValue('icon', 'params');

		if (!empty($icon))
		{
			$icon = JUri::root() . $icon;
		}
	
		$js = "
			var mymap = new redHEREMAP('mapContainer', {
				appId: '" . $appId . "',
        		appCode: '" . $appCode . "',
        		site: 0,
        		zoomLevel: '" . $zoom . "',
        		lat: '" . $lat . "',
        		lng: '" . $lng . "',
        		tiletype: '" . $tiletype . "',
        		scheme: '" . $scheme . "',
        		info: '" . $info . "',
        		icon: '" . $icon . "'
			});

			// Backend
			jQuery('#jform_params_zoom').change(function() {
			    mymap.setZoom(jQuery(this).val());
			});

			jQuery('#jform_params_scheme').change(function() {
			   mymap.setBaseLayer(jQuery('#jform_params_tiletype').val(), jQuery('#jform_params_scheme').val());
			});

			jQuery('#jform_params_tiletype').change(function() {
			   mymap.setBaseLayer(jQuery('#jform_params_tiletype').val(), jQuery('#jform_params_scheme').val());
			});
		";

		return '<div id="mapContainer" style="width: 500px; height: 500px"></div><script>' . $js . '</script>';
	}
}