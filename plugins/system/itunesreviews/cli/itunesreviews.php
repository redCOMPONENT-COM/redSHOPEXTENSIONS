<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/update_cron.php
 */

// Set flag that this is a parent file.
const _JEXEC = 1;

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

require_once JPATH_ROOT . '/plugins/system/itunesreviews/helper/vendor/autoload.php';

/**
 * This script will fetch the update information for all extensions and store
 * them in the database, speeding up your administrator.
 *
 * @since  2.5
 */
class Itunesreviews extends JApplicationCli
{
	/**
	 * @var  JRegistry
	 */
	protected $params = null;

	/**
	 * Entry point for the script
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function doExecute()
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		// important for storing several rows
		JFactory::$application = $this;

		$plugin       = JPluginHelper::getPlugin('system', 'itunesreviews');
		$this->params = new JRegistry;

		// Check if plugin is enabled
		if ($plugin)
		{
			// Get plugin params
			$this->params = $this->params->loadString($plugin->params);
		}

		$this->getItunes();
	}

	/**
	 * @return boolean
	 */
	protected function getItunes()
	{
		$url       = 'https://itunes.apple.com/dk/rss/customerreviews/id=' . $this->params->get('itunes_id') . '/xml';
		$cacheFile = JPATH_ROOT . '/tmp/itunereviews.xml';

		$curl = new \Curl\Curl;
		$curl->get($url);

		if ($curl->error)
		{
			return false;
		}

		// Cache data
		JFile::write($cacheFile, $curl->response->asXml());

		// Clean up.
		$curl->close();

		$xml = simplexml_load_file($cacheFile);

		if ($xml)
		{
			if (isset($xml->entry))
			{
				foreach ($xml->entry as $entry)
				{
					if (isset($entry->content) && $entry->content[0]['type'] == 'text')
					{
						$article        = new JObject;
						$article->title = (string) $entry->title;

						foreach ($entry->content as $content)
						{
							if ($content['type'] != 'text')
							{
								continue;
							}

							$article->introtext = (string) $content;
						}

						$article->created          = JDate::getInstance((string) $entry->updated)->toSql();
						$article->state            = 1;
						$article->catid            = $this->params->get('joomla_category_id');
						$article->created_by_alias = (string) $entry->author->name;

						$table = $this->toJoomlaArticle($article->getProperties());

						// By logic article will be created only one time. So no need to check for INSERT
						if ($table)
						{
							$im = $entry->children('http://itunes.apple.com/rss');
							$rating = (int) $im->rating;

							// Update rating
							$db = JFactory::getDbo();
							$query = $db->getQuery(true);

							// Insert rating
							$query->insert($db->quoteName('#__content_rating'))
								->columns($db->quoteName(array('content_id', 'rating_sum', 'rating_count')))
								->values((int) $table->get('id'), (int) $rating, 1);

							$db->setQuery($query)->execute();
						}
					}
				}
			}
		}
	}

	/**
	 * @param   array  $article
	 *
	 * @return  boolean
	 */
	protected function toJoomlaArticle($article)
	{
		$table = JTable::getInstance('content', 'JTable');

		// Bind data
		if (!$table->bind($article))
		{
			//Handle the errors here however you like (log, display error message, etc.)
			return false;
		}

		// Check the data.
		if (!$table->check())
		{
			//Handle the errors here however you like (log, display error message, etc.)
			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			//Handle the errors here however you like (log, display error message, etc.)
			return false;
		}

		return $table;
	}
}

JApplicationCli::getInstance('Itunesreviews')->execute();
