<?php
return
[
	'NEXO_ID'=>2694,
	
	'SALT_ID'=>1996,
	
	'ORME_ID'=>1998,
	
    'base_currency' => array("BTC","BCH","ETH","LTC"),
	
	'term_currency' => array("EUR"),
	
	'minimum_buy' => array("BTC","ELT","ETH","EUR"),
	
	'maximum_buy' => array("BTC","ELT","ETH","EUR"),
	
	'withdrawal_fee' => array("BTC","ETH","EUR"),
	
	'minimum_withdrawal' => array("ETH","EUR","BTC","ELT","BCH"),
	
	'allowedCurrencyList' => array('ETH','BTC','EUR','ELT','LTC','BTH','BCH','XRP','DASH','MOITA'),
	
	'blockedUsersForSubAdmin'=>array(1,17,114097,114100,114768,114770,114771),
	
	'allowedStatusList' => array("2"=>"Pending","1"=>"Success","0"=>"Failed"),
	
	'subadmin_list'=>array("subadmin@gmail.com"),	
	
	'pdf_save_path'=>'https://webcomclients.in/lendo-loan/setting/save_pdf/',
	
	'euro_worth_stage_0' => array("min"=>0,"max"=>999),
	
	'euro_worth_stage_1' => array("min"=>1000,"max"=>2500),
	
	'euro_worth_stage_2' => array("min"=>2500,"max"=>5000),
	
	'euro_worth_stage_3' => array("min"=>5000,"max"=>7500),
	
	'euro_worth_stage_4' => array("min"=>7500,"max"=>10000),
	
	'euro_worth_stage_5' => array("min"=>10000,"max"=>0),
	
	'country_without_postal'=>array
	(
		"Angola"=>"AO","Antigua and Barbuda"=>"AG","Aruba"=>"AW","Bahamas"=>"BS","Belize"=>"BZ",
		"Benin"=>"BJ","Botswana"=>"BW","Burkina Faso"=>"BF","Burundi"=>"BI","Cameroon"=>"CM",
		"Central African Republic"=>"CF","Comoros"=>"KM","Congo"=>"CG","Congo, the Democratic Republic of the"=>"CD","Cook Islands"=>"CK","Cote d'Ivoire"=>"CI","Djibouti"=>"DJ","Dominica"=>"DM",
		"Equatorial Guinea"=>"GQ","Eritrea"=>"ER","Fiji"=>"FJ","French Southern Territories"=>"TF",
		"Gambia"=>"GM","Ghana"=>"GH","Grenada"=>"GD","Guinea"=>"GN","Guyana"=>"GY","Hong Kong"=>"HK","Ireland"=>"IE","Jamaica"=>"JM","Kenya"=>"KE","Kiribati"=>"KI","Macao"=>"MO","Malawi"=>"MW","Mali"=>"ML","Mauritania"=>"MR","Mauritius"=>"MU","Montserrat"=>"MS","Nauru"=>"NR","Netherlands Antilles"=>"AN","Niue"=>"NU","North Korea"=>"KP","Panama"=>"PA","Qatar"=>"QA","Rwanda"=>"RW","Saint Kitts and Nevis"=>"KN","Saint Lucia"=>"LC","Sao Tome and Principe"=>"ST","Saudi Arabia"=>"SA","Seychelles"=>"SC","Sierra Leone"=>"SL","Solomon Islands"=>"SB","Somalia"=>"SO","South Africa"=>"ZA","Suriname"=>"SR","Syria"=>"SY","Tanzania, United Republic of"=>"TZ","Timor-Leste"=>"TL","Tokelau"=>"TK","Tonga"=>"TO","Trinidad and Tobago"=>"TT","Tuvalu"=>"TV","Uganda"=>"UG","United Arab Emirates"=>"AE","Vanuatu"=>"VU","Yemen"=>"YE","Zimbabwe"=>"ZW" 
	),
];