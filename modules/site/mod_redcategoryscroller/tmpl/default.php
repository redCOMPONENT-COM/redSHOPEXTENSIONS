<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redcategoryscroller
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JHtml::script('com_redshop/redbox.js', false, true);
JHtml::script('com_redshop/attribute.js', false, true);
JHtml::script('com_redshop/common.js', false, true);

?>

<div class="mod_redcategoryscroller" id="mod_redcategoryscroller_<?php echo $module->id ?>">
	<?php if ($scrollCSSOverride == 'yes'): ?>
    <p><?php echo $params->get('pretext', '') ?></p>
    <div style="text-align: <?php echo $scrollAlign ?>;background-color: <?php echo $scrollBackgroundColor ?>; width: <?php echo $scrollWidth ?>px;
            margin: <?php echo $scrollMargin ?>px;">
		<style>
			marquee a{
				color: <?php echo $scrollTextColor ?>;
			}
		</style>
        <marquee
                behavior="<?php echo $scrollBehavior ?>"
                direction="<?php echo $scrollDirection ?>"
                height="<?php echo $scrollHeight ?>"
                width="<?php echo $scrollWidth ?>"
                scrollamount="<?php echo $scrollAmount ?>"
                scrolldelay="<?php echo $scrollDelay ?>"
                truespeed="true" onmouseover="this.stop()" onmouseout="this.start()"
                style="text-align: <?php echo $scrollTextAlign ?>; color: <?php echo $scrollTextColor ?>; font-weight: <?php echo $scrollTextWeight ?>; font-size: <?php echo $scrollTextSize ?>px;">
    <?php else: ?>
    <div style="width: <?php echo $scrollWidth ?>px;text-align: <?php echo $scrollAlign ?>">
        <marquee
                behavior="<?php echo $scrollBehavior ?>"
                direction="<?php echo $scrollDirection ?>"
                height="<?php echo $scrollHeight ?>"
                width="<?php echo $scrollWidth ?>"
                scrollamount="<?php echo $scrollAmount ?>"
                scrolldelay="<?php echo $scrollDelay ?>"
                truespeed="true" onmouseover="this.stop()" onmouseout="this.start()">
    <?php endif; ?>

            <?php if ($scrollDirection == 'left' || $scrollDirection == 'right'): ?>
                    <table>
                        <tr>
			<?php endif; ?>
							<?php $i = 0; ?>

							<?php foreach ($data as $row): ?>
								<?php if ($scrollDirection == 'left' || $scrollDirection == 'right'): ?>
                                    <td style="vertical-align:top;padding: 2px 5px 2px 5px;">
                                        <table width="<?php echo $boxWidth ?>">
								<?php endif; ?>
								<?php
								// Display Product
								$itemIdData = \RedshopHelperRouter::getCategoryItemid($row->id);
								$categoryName = $row->name;

								$itemId = count($itemIdData) > 0 ? $itemIdData->id : \RedshopHelperRouter::getItemId($row->id);

								$link = JRoute::_('index.php?option=com_redshop&view=category&layout=detail&cid=' . $row->id . '&Itemid=' . $itemId);

								if ($boxWidth > 0)
								{
									$categoryName = wordwrap($row->name, $boxWidth / 10, "<br>\n", true);
								}

								if ($row->category_full_image || \Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE'))
								{
									$title = " title='" . $row->name . "' ";
									$alt   = " alt='" . $row->name . "' ";

									$linkImage = REDSHOP_FRONT_IMAGES_ABSPATH . "noimage.jpg";
									$categoryImage = REDSHOP_FRONT_IMAGES_ABSPATH . "noimage.jpg";

									if ($row->category_full_image
                                        && file_exists(REDSHOP_FRONT_IMAGES_RELPATH . 'category/' . $row->category_full_image))
									{
										$categoryImage = \RedshopHelperMedia::watermark('category', $row->category_full_image, $thumbWidth, $thumbHeight, \Redshop::getConfig()->get('WATERMARK_CATEGORY_THUMB_IMAGE'));
										$linkImage     = \RedshopHelperMedia::watermark('category', $row->category_full_image, '', '', \Redshop::getConfig()->get('WATERMARK_CATEGORY_IMAGE'));
									}
									else if (\Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE') && file_exists(REDSHOP_FRONT_IMAGES_RELPATH . 'category/' . \Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE')))
									{
										$categoryImage = \RedshopHelperMedia::watermark('category', \Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE'), $thumbWidth, $thumbHeight, \Redshop::getConfig()->get('WATERMARK_CATEGORY_THUMB_IMAGE'));
										$linkImage     = \RedshopHelperMedia::watermark('category', \Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE'), '', '', \Redshop::getConfig()->get('WATERMARK_CATEGORY_IMAGE'));
									}

									if (\Redshop::getConfig()->get('CAT_IS_LIGHTBOX'))
									{
										$categoryThumb = "<a class='modal' href='" . $row->abs . "' rel=\"{handler: 'image', size: {}}\" " . $title . ">";
									}
									else
									{
										$categoryThumb = "<a href='" . $row->link_category . "' " . $title . ">";
									}

									$categoryThumb .= "<img src='" . $row->abs . "' " . $alt . $title . ">";
									$categoryThumb .= "</a>";
									?>
                                            <tr><td><?php echo $categoryThumb ?></td></tr>
                                    <?php
								}

								if ($show_category_name == 'yes')
								{
									$categoryName    = "<tr><td style='text-align:" . $scrollTextAlign . ";font-weight:" . $scrollTextWeight . ";font-size:" . $scrollTextSize . "px;'><a href='" . $link . "' >" . $categoryName . "</a></td></tr>";
									echo $categoryName;
								}
								?>
								<?php if ($scrollDirection == 'left' || $scrollDirection == 'right'): ?>
                                        </table>
                                    </td>
								<?php else: ?>
									<?php for ($i = 0; $i < $scrollLineCharTimes; $i++): ?>
										<?php echo $scrollLineChar ?>
									<?php endfor; ?>
								<?php endif; ?>

								<?php $i++; ?>
							<?php endforeach; ?>
			<?php if ($scrollDirection == 'left' || $scrollDirection == 'right'): ?>
                        </tr>
                    </table>
            <?php endif; ?>
            </marquee>
        </div>
</div>
