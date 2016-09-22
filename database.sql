-- ################################################## --
-- # Main database tables: to be installed manually # --
-- ################################################## --

-- -----------------------------------------------------
-- Table `settings`
-- -----------------------------------------------------
drop table if exists settings;
CREATE TABLE IF NOT EXISTS `settings` (

  name  varchar(128) not null default '',
  value text,

  primary key ( name )

) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci';

delete from settings;
insert into settings set name='engine.website_name'          , value='Default HNG2 installation';
insert into settings set name='engine.enabled'               , value='true';
insert into settings set name='engine.default_language'      , value='en_US';
insert into settings set name='engine.template'              , value='base';
insert into settings set name='engine.user_session_cookie'   , value='_HNG2SC';
insert into settings set name='engine.user_online_cookie'    , value='_HNG2OC';
insert into settings set name='engine.mail_sender_name'      , value='HNG2 Notifications';
insert into settings set name='engine.mail_sender_email'     , value='nobody@localhost';
insert into settings set name='engine.user_levels'           , value='0 - Unregistered\n1 - Unconfirmed\n50 - Newcomer\n100 - Author\n150 - VIP\n200 - Moderator\n240 - Coadmin\n255 - Admin';

insert into settings set name='modules:accounts.installed'                    , value='true';
insert into settings set name='modules:accounts.enabled'                      , value='true';
insert into settings set name='modules:accounts.register_enabled'             , value='false';
insert into settings set name='modules:accounts.enforce_device_registration'  , value='false';
insert into settings set name='modules:accounts.disable_registrations_widget' , value='false';
insert into settings set name='modules:settings.installed'                    , value='true';
insert into settings set name='modules:settings.enabled'                      , value='true';
insert into settings set name='modules:modules_manager.installed'             , value='true';
insert into settings set name='modules:modules_manager.enabled'               , value='true';
insert into settings set name='modules:modules_manager.disable_cache'         , value='true';

insert into settings set name='engine.recaptcha_public_key' , value='';
insert into settings set name='engine.recaptcha_private_key', value='';

-- -----------------------------------------------------
-- Table `account`
-- -----------------------------------------------------
drop table if exists `account`;
CREATE TABLE `account` (
  `id_account`      bigint unsigned not null default 0,
  `user_name`       VARCHAR(64) NOT NULL DEFAULT '',
  `password`        VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'MD5 hash of password',
  `display_name`    VARCHAR(255) NOT NULL DEFAULT '',
  `email`           VARCHAR(255) NOT NULL DEFAULT '',
  `alt_email`       VARCHAR(255) NOT NULL DEFAULT '',
  `birthdate`       DATE,
  `avatar`          VARCHAR(255) NOT NULL DEFAULT '',
  `profile_banner`  VARCHAR(255) NOT NULL DEFAULT '',
  `signature`       TEXT,
  `bio`             TEXT,
  `homepage_url`    VARCHAR(255) NOT NULL DEFAULT '',
  `country`         VARCHAR(2) NOT NULL DEFAULT '' COMMENT 'ISO code',
  `level`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `state`           ENUM('new','enabled','disabled') NOT NULL DEFAULT 'new',
  `creation_host`   VARCHAR(255) NOT NULL DEFAULT '',
  `creation_date`   DATETIME NOT NULL,
  `last_update`     DATETIME NOT NULL,
  `changelog`       TEXT,
  PRIMARY KEY (`id_account`),
  INDEX   user_name ( user_name(5) ),
  INDEX   email     ( email(5)  ),
  INDEX   alt_email ( alt_email(5) )
) DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

delete from account;
insert into account set
  `id_account`      = concat('10', '0000000000', '000'),
  `user_name`       = 'admin',
  `password`        = '21232f297a57a5a743894a0e4a801fc3', -- admin
  `display_name`    = 'System Administrator',
  `email`           = 'nobody@localhost',
  `alt_email`       = 'nobody2@localhost',
  `country`         = 'us',
  `level`           = 255,
  `state`           = 'enabled',
  `creation_host`   = '127.0.0.1; localhost',
  `creation_date`   = now(),
  `last_update`     = now(),
  `changelog`       = 'Created on installation\n\n'
;

-- -----------------------------------------------------
-- Table `account_devices`
-- -----------------------------------------------------
drop table if exists account_devices;
CREATE TABLE IF NOT EXISTS `account_devices` (
  `id_device`      bigint unsigned not null default 0,
  `id_account`     bigint unsigned not null default 0,
  `device_label`   VARCHAR(255) NOT NULL DEFAULT '',
  `device_header`  VARCHAR(255) NOT NULL DEFAULT '',
  `creation_date`  DATETIME NOT NULL,
  `state`          ENUM('unregistered','enabled','disabled','deleted') NOT NULL DEFAULT 'unregistered',
  `last_activity`  DATETIME NOT NULL,

  PRIMARY KEY            (`id_device`),
  INDEX `id_account`     (`id_account` ASC),
  INDEX `account_device` (`id_account` ASC, `id_device` ASC),
  INDEX `account_agent`  (`id_account` ASC, `device_header`(8) ASC)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci';

-- -----------------------------------------------------
-- Table `account_logins`
-- -----------------------------------------------------
drop table if exists account_logins;
CREATE TABLE IF NOT EXISTS `account_logins` (
  `id_account` bigint unsigned not null default 0,
  `id_device`  bigint unsigned not null default 0,
  `login_date` DATETIME NOT NULL,
  `ip`         VARCHAR(255) NOT NULL DEFAULT '',
  `hostname`   VARCHAR(255) NOT NULL DEFAULT '',
  `location`   VARCHAR(255) NOT NULL DEFAULT '',

  PRIMARY KEY (`id_account` ASC, `id_device` ASC, `login_date` ASC)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci';

-- -----------------------------------------------------
-- Table `account_engine_prefs`
-- -----------------------------------------------------
drop table if exists account_engine_prefs;
CREATE TABLE IF NOT EXISTS `account_engine_prefs` (
  `id_account`     bigint unsigned not null default 0,
  `name`           VARCHAR(128) NOT NULL DEFAULT '',
  `value`          TEXT,
  PRIMARY KEY      (`id_account`, `name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci';

--
--  List of world's countries containing the official short names in English as given in ISO 3166-1,
--  the ISO 3166-1-alpha-2 code provided by the International Organization for Standardization
--  (http://www.iso.org/iso/prods-services/iso3166ma/02iso-3166-code-lists/country_names_and_code_elements)
--  and the ISO alpha-3 code provided by the United Nations Statistics Division
--  (http://unstats.un.org/unsd/methods/m49/m49alpha.htm)
--
--  compiled by Stefan Gabos
--  version 1.2 (last revision: December 09, 2012)
--
--  http://stefangabos.ro/other-projects/list-of-world-countries-with-national-flags/
--

drop table if exists countries;
CREATE TABLE `countries` (
  `name`    varchar(50) NOT NULL default '',
  `alpha_2` varchar(2) NOT NULL default '',
  `alpha_3` varchar(3) NOT NULL default '',
  PRIMARY KEY  (`alpha_2`),
  INDEX   `alpha_3` (`alpha_3`),
  INDEX   `name` (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci';

INSERT INTO `countries` (`name`, `alpha_2`, `alpha_3`) VALUES
  ('Afghanistan', 'af', 'afg'),
  ('Aland Islands', 'ax', 'ala'),
  ('Albania', 'al', 'alb'),
  ('Algeria', 'dz', 'dza'),
  ('American Samoa', 'as', 'asm'),
  ('Andorra', 'ad', 'and'),
  ('Angola', 'ao', 'ago'),
  ('Anguilla', 'ai', 'aia'),
  ('Antarctica', 'aq', ''),
  ('Antigua and Barbuda', 'ag', 'atg'),
  ('Argentina', 'ar', 'arg'),
  ('Armenia', 'am', 'arm'),
  ('Aruba', 'aw', 'abw'),
  ('Australia', 'au', 'aus'),
  ('Austria', 'at', 'aut'),
  ('Azerbaijan', 'az', 'aze'),
  ('Bahamas', 'bs', 'bhs'),
  ('Bahrain', 'bh', 'bhr'),
  ('Bangladesh', 'bd', 'bgd'),
  ('Barbados', 'bb', 'brb'),
  ('Belarus', 'by', 'blr'),
  ('Belgium', 'be', 'bel'),
  ('Belize', 'bz', 'blz'),
  ('Benin', 'bj', 'ben'),
  ('Bermuda', 'bm', 'bmu'),
  ('Bhutan', 'bt', 'btn'),
  ('Bolivia, Plurinational State of', 'bo', 'bol'),
  ('Bonaire, Sint Eustatius and Saba', 'bq', 'bes'),
  ('Bosnia and Herzegovina', 'ba', 'bih'),
  ('Botswana', 'bw', 'bwa'),
  ('Bouvet Island', 'bv', ''),
  ('Brazil', 'br', 'bra'),
  ('British Indian Ocean Territory', 'io', ''),
  ('Brunei Darussalam', 'bn', 'brn'),
  ('Bulgaria', 'bg', 'bgr'),
  ('Burkina Faso', 'bf', 'bfa'),
  ('Burundi', 'bi', 'bdi'),
  ('Cambodia', 'kh', 'khm'),
  ('Cameroon', 'cm', 'cmr'),
  ('Canada', 'ca', 'can'),
  ('Cape Verde', 'cv', 'cpv'),
  ('Cayman Islands', 'ky', 'cym'),
  ('Central African Republic', 'cf', 'caf'),
  ('Chad', 'td', 'tcd'),
  ('Chile', 'cl', 'chl'),
  ('China', 'cn', 'chn'),
  ('Christmas Island', 'cx', ''),
  ('Cocos (Keeling) Islands', 'cc', ''),
  ('Colombia', 'co', 'col'),
  ('Comoros', 'km', 'com'),
  ('Congo', 'cg', 'cog'),
  ('Congo, The Democratic Republic of the', 'cd', 'cod'),
  ('Cook Islands', 'ck', 'cok'),
  ('Costa Rica', 'cr', 'cri'),
  ('Cote d\'Ivoire', 'ci', 'civ'),
  ('Croatia', 'hr', 'hrv'),
  ('Cuba', 'cu', 'cub'),
  ('Curacao', 'cw', 'cuw'),
  ('Cyprus', 'cy', 'cyp'),
  ('Czech Republic', 'cz', 'cze'),
  ('Denmark', 'dk', 'dnk'),
  ('Djibouti', 'dj', 'dji'),
  ('Dominica', 'dm', 'dma'),
  ('Dominican Republic', 'do', 'dom'),
  ('Ecuador', 'ec', 'ecu'),
  ('Egypt', 'eg', 'egy'),
  ('El Salvador', 'sv', 'slv'),
  ('Equatorial Guinea', 'gq', 'gnq'),
  ('Eritrea', 'er', 'eri'),
  ('Estonia', 'ee', 'est'),
  ('Ethiopia', 'et', 'eth'),
  ('Falkland Islands (Malvinas)', 'fk', 'flk'),
  ('Faroe Islands', 'fo', 'fro'),
  ('Fiji', 'fj', 'fji'),
  ('Finland', 'fi', 'fin'),
  ('France', 'fr', 'fra'),
  ('French Guiana', 'gf', 'guf'),
  ('French Polynesia', 'pf', 'pyf'),
  ('French Southern Territories', 'tf', ''),
  ('Gabon', 'ga', 'gab'),
  ('Gambia', 'gm', 'gmb'),
  ('Georgia', 'ge', 'geo'),
  ('Germany', 'de', 'deu'),
  ('Ghana', 'gh', 'gha'),
  ('Gibraltar', 'gi', 'gib'),
  ('Greece', 'gr', 'grc'),
  ('Greenland', 'gl', 'grl'),
  ('Grenada', 'gd', 'grd'),
  ('Guadeloupe', 'gp', 'glp'),
  ('Guam', 'gu', 'gum'),
  ('Guatemala', 'gt', 'gtm'),
  ('Guernsey', 'gg', 'ggy'),
  ('Guinea', 'gn', 'gin'),
  ('Guinea-Bissau', 'gw', 'gnb'),
  ('Guyana', 'gy', 'guy'),
  ('Haiti', 'ht', 'hti'),
  ('Heard Island and McDonald Islands', 'hm', ''),
  ('Holy See (Vatican City State)', 'va', 'vat'),
  ('Honduras', 'hn', 'hnd'),
  ('Hong Kong', 'hk', 'hkg'),
  ('Hungary', 'hu', 'hun'),
  ('Iceland', 'is', 'isl'),
  ('India', 'in', 'ind'),
  ('Indonesia', 'id', 'idn'),
  ('Iran, Islamic Republic of', 'ir', 'irn'),
  ('Iraq', 'iq', 'irq'),
  ('Ireland', 'ie', 'irl'),
  ('Isle of Man', 'im', 'imn'),
  ('Israel', 'il', 'isr'),
  ('Italy', 'it', 'ita'),
  ('Jamaica', 'jm', 'jam'),
  ('Japan', 'jp', 'jpn'),
  ('Jersey', 'je', 'jey'),
  ('Jordan', 'jo', 'jor'),
  ('Kazakhstan', 'kz', 'kaz'),
  ('Kenya', 'ke', 'ken'),
  ('Kiribati', 'ki', 'kir'),
  ('Korea, Democratic People\'s Republic of', 'kp', 'prk'),
  ('Korea, Republic of', 'kr', 'kor'),
  ('Kuwait', 'kw', 'kwt'),
  ('Kyrgyzstan', 'kg', 'kgz'),
  ('Lao People\'s Democratic Republic', 'la', 'lao'),
  ('Latvia', 'lv', 'lva'),
  ('Lebanon', 'lb', 'lbn'),
  ('Lesotho', 'ls', 'lso'),
  ('Liberia', 'lr', 'lbr'),
  ('Libyan Arab Jamahiriya', 'ly', 'lby'),
  ('Liechtenstein', 'li', 'lie'),
  ('Lithuania', 'lt', 'ltu'),
  ('Luxembourg', 'lu', 'lux'),
  ('Macao', 'mo', 'mac'),
  ('Macedonia, The former Yugoslav Republic of', 'mk', 'mkd'),
  ('Madagascar', 'mg', 'mdg'),
  ('Malawi', 'mw', 'mwi'),
  ('Malaysia', 'my', 'mys'),
  ('Maldives', 'mv', 'mdv'),
  ('Mali', 'ml', 'mli'),
  ('Malta', 'mt', 'mlt'),
  ('Marshall Islands', 'mh', 'mhl'),
  ('Martinique', 'mq', 'mtq'),
  ('Mauritania', 'mr', 'mrt'),
  ('Mauritius', 'mu', 'mus'),
  ('Mayotte', 'yt', 'myt'),
  ('Mexico', 'mx', 'mex'),
  ('Micronesia, Federated States of', 'fm', 'fsm'),
  ('Moldova, Republic of', 'md', 'mda'),
  ('Monaco', 'mc', 'mco'),
  ('Mongolia', 'mn', 'mng'),
  ('Montenegro', 'me', 'mne'),
  ('Montserrat', 'ms', 'msr'),
  ('Morocco', 'ma', 'mar'),
  ('Mozambique', 'mz', 'moz'),
  ('Myanmar', 'mm', 'mmr'),
  ('Namibia', 'na', 'nam'),
  ('Nauru', 'nr', 'nru'),
  ('Nepal', 'np', 'npl'),
  ('Netherlands', 'nl', 'nld'),
  ('New Caledonia', 'nc', 'ncl'),
  ('New Zealand', 'nz', 'nzl'),
  ('Nicaragua', 'ni', 'nic'),
  ('Niger', 'ne', 'ner'),
  ('Nigeria', 'ng', 'nga'),
  ('Niue', 'nu', 'niu'),
  ('Norfolk Island', 'nf', 'nfk'),
  ('Northern Mariana Islands', 'mp', 'mnp'),
  ('Norway', 'no', 'nor'),
  ('Oman', 'om', 'omn'),
  ('Pakistan', 'pk', 'pak'),
  ('Palau', 'pw', 'plw'),
  ('Palestinian Territory, Occupied', 'ps', 'pse'),
  ('Panama', 'pa', 'pan'),
  ('Papua New Guinea', 'pg', 'png'),
  ('Paraguay', 'py', 'pry'),
  ('Peru', 'pe', 'per'),
  ('Philippines', 'ph', 'phl'),
  ('Pitcairn', 'pn', 'pcn'),
  ('Poland', 'pl', 'pol'),
  ('Portugal', 'pt', 'prt'),
  ('Puerto Rico', 'pr', 'pri'),
  ('Qatar', 'qa', 'qat'),
  ('Reunion', 're', 'reu'),
  ('Romania', 'ro', 'rou'),
  ('Russian Federation', 'ru', 'rus'),
  ('Rwanda', 'rw', 'rwa'),
  ('Saint Barthelemy', 'bl', 'blm'),
  ('Saint Helena, Ascension and Tristan Da Cunha', 'sh', 'shn'),
  ('Saint Kitts and Nevis', 'kn', 'kna'),
  ('Saint Lucia', 'lc', 'lca'),
  ('Saint Martin (French Part)', 'mf', 'maf'),
  ('Saint Pierre and Miquelon', 'pm', 'spm'),
  ('Saint Vincent and The Grenadines', 'vc', 'vct'),
  ('Samoa', 'ws', 'wsm'),
  ('San Marino', 'sm', 'smr'),
  ('Sao Tome and Principe', 'st', 'stp'),
  ('Saudi Arabia', 'sa', 'sau'),
  ('Senegal', 'sn', 'sen'),
  ('Serbia', 'rs', 'srb'),
  ('Seychelles', 'sc', 'syc'),
  ('Sierra Leone', 'sl', 'sle'),
  ('Singapore', 'sg', 'sgp'),
  ('Sint Maarten (Dutch Part)', 'sx', 'sxm'),
  ('Slovakia', 'sk', 'svk'),
  ('Slovenia', 'si', 'svn'),
  ('Solomon Islands', 'sb', 'slb'),
  ('Somalia', 'so', 'som'),
  ('South Africa', 'za', 'zaf'),
  ('South Georgia and The South Sandwich Islands', 'gs', ''),
  ('South Sudan', 'ss', 'ssd'),
  ('Spain', 'es', 'esp'),
  ('Sri Lanka', 'lk', 'lka'),
  ('Sudan', 'sd', 'sdn'),
  ('Suriname', 'sr', 'sur'),
  ('Svalbard and Jan Mayen', 'sj', 'sjm'),
  ('Swaziland', 'sz', 'swz'),
  ('Sweden', 'se', 'swe'),
  ('Switzerland', 'ch', 'che'),
  ('Syrian Arab Republic', 'sy', 'syr'),
  ('Taiwan, Province of China', 'tw', ''),
  ('Tajikistan', 'tj', 'tjk'),
  ('Tanzania, United Republic of', 'tz', 'tza'),
  ('Thailand', 'th', 'tha'),
  ('Timor-Leste', 'tl', 'tls'),
  ('Togo', 'tg', 'tgo'),
  ('Tokelau', 'tk', 'tkl'),
  ('Tonga', 'to', 'ton'),
  ('Trinidad and Tobago', 'tt', 'tto'),
  ('Tunisia', 'tn', 'tun'),
  ('Turkey', 'tr', 'tur'),
  ('Turkmenistan', 'tm', 'tkm'),
  ('Turks and Caicos Islands', 'tc', 'tca'),
  ('Tuvalu', 'tv', 'tuv'),
  ('Uganda', 'ug', 'uga'),
  ('Ukraine', 'ua', 'ukr'),
  ('United Arab Emirates', 'ae', 'are'),
  ('United Kingdom', 'gb', 'gbr'),
  ('United States', 'us', 'usa'),
  ('United States Minor Outlying Islands', 'um', ''),
  ('Uruguay', 'uy', 'ury'),
  ('Uzbekistan', 'uz', 'uzb'),
  ('Vanuatu', 'vu', 'vut'),
  ('Venezuela, Bolivarian Republic of', 've', 'ven'),
  ('Viet Nam', 'vn', 'vnm'),
  ('Virgin Islands, British', 'vg', 'vgb'),
  ('Virgin Islands, U.S.', 'vi', 'vir'),
  ('Wallis and Futuna', 'wf', 'wlf'),
  ('Western Sahara', 'eh', 'esh'),
  ('Yemen', 'ye', 'yem'),
  ('Zambia', 'zm', 'zmb'),
  ('Zimbabwe', 'zw', 'zwe');
