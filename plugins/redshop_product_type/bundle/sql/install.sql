CREATE TABLE IF NOT EXISTS `#__redshop_product_bundle` (
  `product_id` int(11) NOT NULL,
  `bundle_id` int(11) NOT NULL,
  `bundle_name` varchar(250) NOT NULL,
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`bundle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='redSHOP Product Bundle';

CREATE TABLE IF NOT EXISTS `#__redshop_order_bundle` (
	  `order_item_id` int(11) NOT NULL,
	  `bundle_id` int(11) NOT NULL,
	  `product_id` int(11) NOT NULL,
	  `property_id` int(11) NOT NULL,
  PRIMARY KEY (`order_item_id`,`bundle_id`,`product_id`,`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `#__redshop_template` (`name`, `section`, `file_name`, `order_status`, `payment_methods`, `published`, `shipping_methods`, `checked_out`, `checked_out_time`, `created_by`, `created_time`, `modified_by`, `modified_date`) VALUES
	('bundle', 'bundle_template', 'bundle_template', '', '', 1, '', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00');