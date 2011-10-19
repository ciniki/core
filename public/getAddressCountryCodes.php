<?php
//
// Description
// -----------
// This module will return a list of country codes
// for the world in various formats.
// Initial load information was from wikipedia  http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
//
// eg: CA - Canada, US - United States, etc.
//
// Info
// ----
// status:		beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// standard:	(optional) The standard to get the codes for.  This can be one of the following:
//				
//				_ 'default' - ISO_3166-1-alpha-2
//				_ 'ISO_3166-1-alpha-2' - this will return the list of 2 digit country codes, and their short names.
//				
//				More formats may be required in the future.
// format:		
//
// Returns
// -------
//
// <countries>
//     <country id="CA" name="CANADA" />
//     ...
// </countries>
//
function moss_core_getAddressCountryCodes($moss) {
	//
	// Check access restrictions to checkAPIKey
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.getAddressCountryCodes');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $moss['response']['format'] != 'tmpl' 
		&& $moss['response']['format'] != 'php_serial' 
		&& $moss['response']['format'] != 'rest' 
		) {
		return array('stat'=>'fail', 'err'=>array('code'=>'6', 'msg'=>'Invalid arguments', 'pmsg'=>'Response format must be specified.'));
	}

	//
	// Main ISO 3166 website: http://www.iso.org/iso/country_codes.htm
	// data was from -> http://www.iso.org/iso/list-en1-semic-3.txt
	//
	$data_tmpl_keys = array('/\{\$cc\}/', '/\{\$name\}/');
	$i = 0;
	$data[$i++] = array("AF", "AFGHANISTAN");
	$data[$i++] = array("AX", "ÅLAND ISLANDS");
	$data[$i++] = array("AL", "ALBANIA");
	$data[$i++] = array("DZ", "ALGERIA");
	$data[$i++] = array("AS", "AMERICAN SAMOA");
	$data[$i++] = array("AD", "ANDORRA");
	$data[$i++] = array("AO", "ANGOLA");
	$data[$i++] = array("AI", "ANGUILLA");
	$data[$i++] = array("AQ", "ANTARCTICA");
	$data[$i++] = array("AG", "ANTIGUA AND BARBUDA");
	$data[$i++] = array("AR", "ARGENTINA");
	$data[$i++] = array("AM", "ARMENIA");
	$data[$i++] = array("AW", "ARUBA");
	$data[$i++] = array("AU", "AUSTRALIA");
	$data[$i++] = array("AT", "AUSTRIA");
	$data[$i++] = array("AZ", "AZERBAIJAN");
	$data[$i++] = array("BS", "BAHAMAS");
	$data[$i++] = array("BH", "BAHRAIN");
	$data[$i++] = array("BD", "BANGLADESH");
	$data[$i++] = array("BB", "BARBADOS");
	$data[$i++] = array("BY", "BELARUS");
	$data[$i++] = array("BE", "BELGIUM");
	$data[$i++] = array("BZ", "BELIZE");
	$data[$i++] = array("BJ", "BENIN");
	$data[$i++] = array("BM", "BERMUDA");
	$data[$i++] = array("BT", "BHUTAN");
	$data[$i++] = array("BO", "BOLIVIA, PLURINATIONAL STATE OF");
	$data[$i++] = array("BA", "BOSNIA AND HERZEGOVINA");
	$data[$i++] = array("BW", "BOTSWANA");
	$data[$i++] = array("BV", "BOUVET ISLAND");
	$data[$i++] = array("BR", "BRAZIL");
	$data[$i++] = array("IO", "BRITISH INDIAN OCEAN TERRITORY");
	$data[$i++] = array("BN", "BRUNEI DARUSSALAM");
	$data[$i++] = array("BG", "BULGARIA");
	$data[$i++] = array("BF", "BURKINA FASO");
	$data[$i++] = array("BI", "BURUNDI");
	$data[$i++] = array("KH", "CAMBODIA");
	$data[$i++] = array("CM", "CAMEROON");
	$data[$i++] = array("CA", "CANADA");
	$data[$i++] = array("CV", "CAPE VERDE");
	$data[$i++] = array("KY", "CAYMAN ISLANDS");
	$data[$i++] = array("CF", "CENTRAL AFRICAN REPUBLIC");
	$data[$i++] = array("TD", "CHAD");
	$data[$i++] = array("CL", "CHILE");
	$data[$i++] = array("CN", "CHINA");
	$data[$i++] = array("CX", "CHRISTMAS ISLAND");
	$data[$i++] = array("CC", "COCOS (KEELING) ISLANDS");
	$data[$i++] = array("CO", "COLOMBIA");
	$data[$i++] = array("KM", "COMOROS");
	$data[$i++] = array("CG", "CONGO");
	$data[$i++] = array("CD", "CONGO, THE DEMOCRATIC REPUBLIC OF THE");
	$data[$i++] = array("CK", "COOK ISLANDS");
	$data[$i++] = array("CR", "COSTA RICA");
	$data[$i++] = array("CI", "CÔTE D'IVOIRE");
	$data[$i++] = array("HR", "CROATIA");
	$data[$i++] = array("CU", "CUBA");
	$data[$i++] = array("CY", "CYPRUS");
	$data[$i++] = array("CZ", "CZECH REPUBLIC");
	$data[$i++] = array("DK", "DENMARK");
	$data[$i++] = array("DJ", "DJIBOUTI");
	$data[$i++] = array("DM", "DOMINICA");
	$data[$i++] = array("DO", "DOMINICAN REPUBLIC");
	$data[$i++] = array("EC", "ECUADOR");
	$data[$i++] = array("EG", "EGYPT");
	$data[$i++] = array("SV", "EL SALVADOR");
	$data[$i++] = array("GQ", "EQUATORIAL GUINEA");
	$data[$i++] = array("ER", "ERITREA");
	$data[$i++] = array("EE", "ESTONIA");
	$data[$i++] = array("ET", "ETHIOPIA");
	$data[$i++] = array("FK", "FALKLAND ISLANDS (MALVINAS)");
	$data[$i++] = array("FO", "FAROE ISLANDS");
	$data[$i++] = array("FJ", "FIJI");
	$data[$i++] = array("FI", "FINLAND");
	$data[$i++] = array("FR", "FRANCE");
	$data[$i++] = array("GF", "FRENCH GUIANA");
	$data[$i++] = array("PF", "FRENCH POLYNESIA");
	$data[$i++] = array("TF", "FRENCH SOUTHERN TERRITORIES");
	$data[$i++] = array("GA", "GABON");
	$data[$i++] = array("GM", "GAMBIA");
	$data[$i++] = array("GE", "GEORGIA");
	$data[$i++] = array("DE", "GERMANY");
	$data[$i++] = array("GH", "GHANA");
	$data[$i++] = array("GI", "GIBRALTAR");
	$data[$i++] = array("GR", "GREECE");
	$data[$i++] = array("GL", "GREENLAND");
	$data[$i++] = array("GD", "GRENADA");
	$data[$i++] = array("GP", "GUADELOUPE");
	$data[$i++] = array("GU", "GUAM");
	$data[$i++] = array("GT", "GUATEMALA");
	$data[$i++] = array("GG", "GUERNSEY");
	$data[$i++] = array("GN", "GUINEA");
	$data[$i++] = array("GW", "GUINEA-BISSAU");
	$data[$i++] = array("GY", "GUYANA");
	$data[$i++] = array("HT", "HAITI");
	$data[$i++] = array("HM", "HEARD ISLAND AND MCDONALD ISLANDS");
	$data[$i++] = array("VA", "HOLY SEE (VATICAN CITY STATE)");
	$data[$i++] = array("HN", "HONDURAS");
	$data[$i++] = array("HK", "HONG KONG");
	$data[$i++] = array("HU", "HUNGARY");
	$data[$i++] = array("IS", "ICELAND");
	$data[$i++] = array("IN", "INDIA");
	$data[$i++] = array("ID", "INDONESIA");
	$data[$i++] = array("IR", "IRAN, ISLAMIC REPUBLIC OF");
	$data[$i++] = array("IQ", "IRAQ");
	$data[$i++] = array("IE", "IRELAND");
	$data[$i++] = array("IM", "ISLE OF MAN");
	$data[$i++] = array("IL", "ISRAEL");
	$data[$i++] = array("IT", "ITALY");
	$data[$i++] = array("JM", "JAMAICA");
	$data[$i++] = array("JP", "JAPAN");
	$data[$i++] = array("JE", "JERSEY");
	$data[$i++] = array("JO", "JORDAN");
	$data[$i++] = array("KZ", "KAZAKHSTAN");
	$data[$i++] = array("KE", "KENYA");
	$data[$i++] = array("KI", "KIRIBATI");
	$data[$i++] = array("KP", "KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF");
	$data[$i++] = array("KR", "KOREA, REPUBLIC OF");
	$data[$i++] = array("KW", "KUWAIT");
	$data[$i++] = array("KG", "KYRGYZSTAN");
	$data[$i++] = array("LA", "LAO PEOPLE'S DEMOCRATIC REPUBLIC");
	$data[$i++] = array("LV", "LATVIA");
	$data[$i++] = array("LB", "LEBANON");
	$data[$i++] = array("LS", "LESOTHO");
	$data[$i++] = array("LR", "LIBERIA");
	$data[$i++] = array("LY", "LIBYAN ARAB JAMAHIRIYA");
	$data[$i++] = array("LI", "LIECHTENSTEIN");
	$data[$i++] = array("LT", "LITHUANIA");
	$data[$i++] = array("LU", "LUXEMBOURG");
	$data[$i++] = array("MO", "MACAO");
	$data[$i++] = array("MK", "MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF");
	$data[$i++] = array("MG", "MADAGASCAR");
	$data[$i++] = array("MW", "MALAWI");
	$data[$i++] = array("MY", "MALAYSIA");
	$data[$i++] = array("MV", "MALDIVES");
	$data[$i++] = array("ML", "MALI");
	$data[$i++] = array("MT", "MALTA");
	$data[$i++] = array("MH", "MARSHALL ISLANDS");
	$data[$i++] = array("MQ", "MARTINIQUE");
	$data[$i++] = array("MR", "MAURITANIA");
	$data[$i++] = array("MU", "MAURITIUS");
	$data[$i++] = array("YT", "MAYOTTE");
	$data[$i++] = array("MX", "MEXICO");
	$data[$i++] = array("FM", "MICRONESIA, FEDERATED STATES OF");
	$data[$i++] = array("MD", "MOLDOVA, REPUBLIC OF");
	$data[$i++] = array("MC", "MONACO");
	$data[$i++] = array("MN", "MONGOLIA");
	$data[$i++] = array("ME", "MONTENEGRO");
	$data[$i++] = array("MS", "MONTSERRAT");
	$data[$i++] = array("MA", "MOROCCO");
	$data[$i++] = array("MZ", "MOZAMBIQUE");
	$data[$i++] = array("MM", "MYANMAR");
	$data[$i++] = array("NA", "NAMIBIA");
	$data[$i++] = array("NR", "NAURU");
	$data[$i++] = array("NP", "NEPAL");
	$data[$i++] = array("NL", "NETHERLANDS");
	$data[$i++] = array("AN", "NETHERLANDS ANTILLES");
	$data[$i++] = array("NC", "NEW CALEDONIA");
	$data[$i++] = array("NZ", "NEW ZEALAND");
	$data[$i++] = array("NI", "NICARAGUA");
	$data[$i++] = array("NE", "NIGER");
	$data[$i++] = array("NG", "NIGERIA");
	$data[$i++] = array("NU", "NIUE");
	$data[$i++] = array("NF", "NORFOLK ISLAND");
	$data[$i++] = array("MP", "NORTHERN MARIANA ISLANDS");
	$data[$i++] = array("NO", "NORWAY");
	$data[$i++] = array("OM", "OMAN");
	$data[$i++] = array("PK", "PAKISTAN");
	$data[$i++] = array("PW", "PALAU");
	$data[$i++] = array("PS", "PALESTINIAN TERRITORY, OCCUPIED");
	$data[$i++] = array("PA", "PANAMA");
	$data[$i++] = array("PG", "PAPUA NEW GUINEA");
	$data[$i++] = array("PY", "PARAGUAY");
	$data[$i++] = array("PE", "PERU");
	$data[$i++] = array("PH", "PHILIPPINES");
	$data[$i++] = array("PN", "PITCAIRN");
	$data[$i++] = array("PL", "POLAND");
	$data[$i++] = array("PT", "PORTUGAL");
	$data[$i++] = array("PR", "PUERTO RICO");
	$data[$i++] = array("QA", "QATAR");
	$data[$i++] = array("RE", "RÉUNION");
	$data[$i++] = array("RO", "ROMANIA");
	$data[$i++] = array("RU", "RUSSIAN FEDERATION");
	$data[$i++] = array("RW", "RWANDA");
	$data[$i++] = array("BL", "SAINT BARTHÉLEMY");
	$data[$i++] = array("SH", "SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA");
	$data[$i++] = array("KN", "SAINT KITTS AND NEVIS");
	$data[$i++] = array("LC", "SAINT LUCIA");
	$data[$i++] = array("MF", "SAINT MARTIN");
	$data[$i++] = array("PM", "SAINT PIERRE AND MIQUELON");
	$data[$i++] = array("VC", "SAINT VINCENT AND THE GRENADINES");
	$data[$i++] = array("WS", "SAMOA");
	$data[$i++] = array("SM", "SAN MARINO");
	$data[$i++] = array("ST", "SAO TOME AND PRINCIPE");
	$data[$i++] = array("SA", "SAUDI ARABIA");
	$data[$i++] = array("SN", "SENEGAL");
	$data[$i++] = array("RS", "SERBIA");
	$data[$i++] = array("SC", "SEYCHELLES");
	$data[$i++] = array("SL", "SIERRA LEONE");
	$data[$i++] = array("SG", "SINGAPORE");
	$data[$i++] = array("SK", "SLOVAKIA");
	$data[$i++] = array("SI", "SLOVENIA");
	$data[$i++] = array("SB", "SOLOMON ISLANDS");
	$data[$i++] = array("SO", "SOMALIA");
	$data[$i++] = array("ZA", "SOUTH AFRICA");
	$data[$i++] = array("GS", "SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS");
	$data[$i++] = array("ES", "SPAIN");
	$data[$i++] = array("LK", "SRI LANKA");
	$data[$i++] = array("SD", "SUDAN");
	$data[$i++] = array("SR", "SURINAME");
	$data[$i++] = array("SJ", "SVALBARD AND JAN MAYEN");
	$data[$i++] = array("SZ", "SWAZILAND");
	$data[$i++] = array("SE", "SWEDEN");
	$data[$i++] = array("CH", "SWITZERLAND");
	$data[$i++] = array("SY", "SYRIAN ARAB REPUBLIC");
	$data[$i++] = array("TW", "TAIWAN, PROVINCE OF CHINA");
	$data[$i++] = array("TJ", "TAJIKISTAN");
	$data[$i++] = array("TZ", "TANZANIA, UNITED REPUBLIC OF");
	$data[$i++] = array("TH", "THAILAND");
	$data[$i++] = array("TL", "TIMOR-LESTE");
	$data[$i++] = array("TG", "TOGO");
	$data[$i++] = array("TK", "TOKELAU");
	$data[$i++] = array("TO", "TONGA");
	$data[$i++] = array("TT", "TRINIDAD AND TOBAGO");
	$data[$i++] = array("TN", "TUNISIA");
	$data[$i++] = array("TR", "TURKEY");
	$data[$i++] = array("TM", "TURKMENISTAN");
	$data[$i++] = array("TC", "TURKS AND CAICOS ISLANDS");
	$data[$i++] = array("TV", "TUVALU");
	$data[$i++] = array("UG", "UGANDA");
	$data[$i++] = array("UA", "UKRAINE");
	$data[$i++] = array("AE", "UNITED ARAB EMIRATES");
	$data[$i++] = array("GB", "UNITED KINGDOM");
	$data[$i++] = array("US", "UNITED STATES");
	$data[$i++] = array("UM", "UNITED STATES MINOR OUTLYING ISLANDS");
	$data[$i++] = array("UY", "URUGUAY");
	$data[$i++] = array("UZ", "UZBEKISTAN");
	$data[$i++] = array("VU", "VANUATU");
	$data[$i++] = array("VE", "VENEZUELA, BOLIVARIAN REPUBLIC OF");
	$data[$i++] = array("VN", "VIET NAM");
	$data[$i++] = array("VG", "VIRGIN ISLANDS, BRITISH");
	$data[$i++] = array("VI", "VIRGIN ISLANDS, U.S.");
	$data[$i++] = array("WF", "WALLIS AND FUTUNA");
	$data[$i++] = array("EH", "WESTERN SAHARA");
	$data[$i++] = array("YE", "YEMEN");
	$data[$i++] = array("ZM", "ZAMBIA");
	$data[$i++] = array("ZW", "ZIMBABWE");

//
//	FIXME: Right now, all requests return the same results, but should be changed in the future.
//
//	if( preg_match('/ISO_3166-1-alpha-2/i', $standard) ) {

	$rsp = array();
	$rsp['stat'] = 'ok';
	if( $moss['response']['format'] == 'php_serial' ) {
		$rsp['countries'] = array();
		$rsp['countries']['count'] = 0;
		$rsp['countries']['data'] = array();
		foreach($data as $country) {
			$rsp['countries'][$rsp['countries']['count']++] = array('id'=>$country[0], 'name'=>$country[1]);
		}
	}

	//
	// Default to return a REST formated XML response
	//
	else {
		$rsp['countries'] = array();
		$rsp['countries']['count'] = 0;
		$rsp['countries']['xml'] = '';

		//
		// The default is to respond using a template, even if we have to force it to
		// a xml template.
		//
		$template = '<country id="{$cc}" name="{$name}" />';
		if( $moss['response']['format'] == 'html' ) {
			$template = '<li id="{$cc}">{$name}</li>';
		} elseif( $moss['response']['format'] == 'tmpl' && isset($moss['response']['template']) ) {
			$template = $moss['response']['template'];
		}

		foreach($data as $country) {
			$rsp['countries']['count']++;
			$rsp['countries']['xml'] .= preg_replace($data_tmpl_keys, $country, $template);
		}
	} 

//	}
	
	return $rsp;
}
?>
