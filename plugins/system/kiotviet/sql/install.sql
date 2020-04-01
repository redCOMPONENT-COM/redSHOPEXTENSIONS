CREATE TABLE `#__kiotviet_category_mapping` (
  `kv_category_id` varchar(20) NOT NULL,
  `rs_category_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `#__kiotviet_category_mapping`
  ADD PRIMARY KEY (`kv_category_id`),
  ADD UNIQUE KEY `rs_category_id` (`rs_category_id`);
COMMIT;