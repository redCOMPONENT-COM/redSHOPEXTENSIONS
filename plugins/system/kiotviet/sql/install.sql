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