DROP TABLE IF EXISTS `#__redshop_product_bundle`;
DROP TABLE IF EXISTS `#__redshop_order_bundle`;

DELETE FROM `#__redshop_template` WHERE `name`='bundle' AND `section`='bundle_template';