DROP TABLE IF EXISTS `#__kiotviet_category_mapping` ;
CREATE TABLE `#__kiotviet_category_mapping` (
  `kv_category_id` varchar(20) NOT NULL,
  `rs_category_id` varchar(20) NOT NULL
)
ENGINE=InnoDB
DEFAULT CHARACTER SET = utf8;

DROP TABLE IF EXISTS `#__kiotviet_setting_product` ;
CREATE TABLE IF NOT EXISTS `#__kiotviet_setting_product` (
  `product_id` INT (11) NOT NULL,
  `key_setting` varchar(250) NOT NULL,
  `value_setting` varchar(250) NOT NULL
) ENGINE=InnoDB
DEFAULT CHARACTER SET = utf8;

ALTER TABLE `#__kiotviet_category_mapping`
  ADD PRIMARY KEY (`kv_category_id`),
  ADD UNIQUE KEY `rs_category_id` (`rs_category_id`);
COMMIT;

INSERT IGNORE INTO `#__redshop_fields` (`title`, `name`, `type`, `desc`, `class`, `section`, `maxlength`, `cols`, `rows`, `size`, `show_in_front`, `required`, `published`, `publish_up`, `publish_down`, `display_in_product`, `ordering`, `display_in_checkout`, `checked_out`, `checked_out_time`, `created_date`, `created_by`, `modified_date`, `modified_by`)
VALUES
	('Kiotviet Orders', 'rs_kiotviet_orders', '1', '', '', '20', 0, 0, 0, 0, 0, 1, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, 7, 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL);
