DROP TABLE IF EXISTS `zp_test`;
CREATE TABLE IF NOT EXISTS `zp_test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `price` float NOT NULL,
  `modified_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO `zp_test` (`id`, `code`, `name`, `price`, `modified_at`) VALUES
(1, 'apple', '红富士苹果', 6.8, '2017-10-22 00:00:00'),
(2, 'pear', '砀山梨', 2.5, '2017-10-23 00:00:00');
