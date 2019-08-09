<html>
<title>Guerilla Dungeons</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.23/moment-timezone-with-data.min.js" type="text/javascript"></script>
<script src="./guerrilla.js" type="text/javascript"></script>
<style>
.highlight{
	background-color:powderblue;
}
.highlight .highlight{
	font-weight: bold;
}
.group-tag{
	display: block;
	text-align: center;
	line-height: 1em;
}
.dungeon-icon{
	width: 50px;
	height: 50px;
}
.float{
	float: left;
}
</style>

</head>
<body>
<div>

<button onclick="switchTimezone();">Timezone: <span id="timezone"></span></button>

<button onclick="switchRegion();">Switch Region: <span id="region"></span></button><button onclick="pickMode('group');">By Group</button><button onclick="pickMode('schedule');">By Time</button><button onclick="pickMode('next');">By Countdown</button>

<?php 
date_default_timezone_set('UTC');
ini_set('allow_url_fopen', 1);

$icon = get_json('./guerrilla_icon.json');
function tag($tag, $inner, $attributes = ''){
	return "<$tag $attributes>$inner</$tag>";
}
function get_json($url){
	$data = file_get_contents($url);
	return json_decode($data, true);
}
function get_icon($dungeon_name){
	if(substr($dungeon_name, 0, 1) == '$'){
		$dungeon_name = explode('$', $dungeon_name)[2];
	}
	global $icon;
	$icon_url = $icon['iconURL'] . $icon[$dungeon_name]['i'];
	$dungeon_url = $icon[$dungeon_name]['d'] == '' ? '#' : $icon['dungeonURL'] . $icon[$dungeon_name]['d'];
	return tag('a', "<img src=\"$icon_url\" class=\"dungeon-icon\">", "href=\"$dungeon_url\" title=\"$dungeon_name\"");
}
function get_orb($orb){
	$orb_id = ['red' => '1', 'blue' => '2', 'green' => '3'];
	if(array_key_exists($orb, $orb_id)){
		$id = $orb_id[$orb];
		return "<img src=\"https://pad.protic.site/wp-content/uploads/pad-orbs/$id.png\" width=\"15\" height=\"15\">";
	}else{
		return $orb;
	}
}
$tform = 'm/d H:i e';
function get_table_group_rows($dungeon_name, $d_entries, $group_list){
	global $tform;
	$empty = true;
	$row = tag('td', get_icon($dungeon_name));
	foreach($group_list as $group){
		if(array_key_exists($group, $d_entries)){
			$cells = array();
			$cell_highlight = '';
			foreach($d_entries[$group] as $entry){
				if($entry['start_timestamp'] <= time() && $entry['end_timestamp'] >= time()){
					$cell_highlight = 'class="highlight"';
					$cells[$entry['start_timestamp']] = tag('div', date($tform, $entry['start_timestamp']), 'class="highlight timestamp" data-timestamp="' . (String) $entry['start_timestamp'] . '"');
				}else{
					$cells[$entry['start_timestamp']] = tag('div', date($tform, $entry['start_timestamp']), 'class="timestamp" data-timestamp="' . (String) $entry['start_timestamp'] . '"');
				}
			}
			$row = $row . tag('td', implode($cells), $cell_highlight);
			$empty = false;
		}else{
			$row = $row . tag('td', '');
		}
	}
	return $empty ? '' :tag('tr', $row);
}
function get_table_time_rows($start_time, $t_entries, $start_end, $group_list){
	global $tform;
	$row = tag('td', date($tform, $start_time), 'class="timestamp" data-timestamp="' . (String) $start_time . '"');
	$empty = true;
	foreach($group_list as $group){
		if(array_key_exists($group, $t_entries)){
			$cells = array();
			foreach($t_entries[$group] as $entry){
				$cells[$entry['dungeon_name']] = tag('div', get_icon($entry['dungeon_name']));
			}
			$row = $row . tag('td', implode($cells));
			$empty = false;
		}else{
			$row = $row . tag('td', '');
		}
	}
	if($empty){
		return '';
	}
	if($start_time <= time() && $start_end[$start_time] >= time()){
		return tag('tr', $row, 'class="highlight"');
	}else{
		return tag('tr', $row);
	}
}

/*
	$url_na: data source for NA schedule
	$url_jp: data source for JP schedule
*/
function get_guerrilla_tables($url_na, $url_jp){
	$by_dungeon_group = array('JP' => array(), 'NA' => array());
	$by_time = array('JP' => array(), 'NA' => array());
	$start_end = array();
	$day = array(
		'JP' => array(
			'start' => (new DateTime('today', new DateTimeZone('+0900')))->getTimestamp(), 
			'end' => (new DateTime('tomorrow', new DateTimeZone('+0900')))->getTimestamp()
		),
		'NA' => array(
			'start' => (new DateTime('today', new DateTimeZone('-0800')))->getTimestamp(), 
			'end' => (new DateTime('tomorrow', new DateTimeZone('-0800')))->getTimestamp()
		)
	);
	$now = time();
	
	$gd = array();
	$gd['NA'] = get_json($url_na);
	$gd['JP'] = get_json($url_jp);
	foreach($gd as $server => $array){
		foreach($array as $value){
			if (strtoupper($value['server']) != $server
				|| $value['start_timestamp'] <= $day[$server]['start']
				|| $value['end_timestamp'] <= $day[$server]['start']
				|| is_null($value['dungeon']) 
				|| $value['dungeon']['dungeon_type'] != 'guerrilla'){
				continue;
			}
			$dungeon = array(
				'dungeon_name' => $value['dungeon']['clean_name'],
				'group' => $value['group'],
				'start_timestamp' => $value['start_timestamp'],
				'end_timestamp' => $value['end_timestamp'],
				'server' => $server
			);
			if($dungeon['start_timestamp'] >= $day[$server]['start'] 
			&& $dungeon['end_timestamp'] <= $day[$server]['end']){
				$by_dungeon_group[$server][$dungeon['dungeon_name']][$dungeon['group']][] = $dungeon;
			}
			if($dungeon['end_timestamp'] >= $now){
				$by_time[$server][$dungeon['start_timestamp']][$dungeon['group']][] = $dungeon;
			}
			$start_end[$dungeon['start_timestamp']] = $dungeon['end_timestamp'];
		}
	}

	$out = '';
	foreach(['JP', 'NA'] as $server){
		$server_out = '';
		$tbl_gs = '<tr><td>Dungeon</td><td>' . get_orb('red') . '</td><td>' . get_orb('blue') . '</td><td>' . get_orb('green') . '</td></tr>';
		foreach($by_dungeon_group[$server] as $dungeon_name => $d_entries){
			$tbl_gs .= get_table_group_rows($dungeon_name, $d_entries, ['red', 'blue', 'green']);
		}
		ksort($by_time[$server]);
		$tbl_ts = '<tr><td>Time</td><td>' . get_orb('red') . '</td><td>' . get_orb('blue') . '</td><td>' . get_orb('green') . '</td></tr>';
		$tbl_tr = '<tr><td>Time Remaining</td><td>Dungeon</td></tr><tr class="tr-none"><td>--h --m</td><td>None</td></tr>';
		$tbl_tu = '<tr><td>Time Until</td><td>Dungeon</td></tr><tr class="tu-none"><td>--h --m</td><td>None</td></tr>';
		foreach($by_time[$server] as $start_time => $t_entries){
			if($start_end[$start_time] >= time()){
				$tbl_ts .= get_table_time_rows($start_time, $t_entries, $start_end, ['red', 'blue', 'green']);
				
				$row_tru = '';
				foreach(['red', 'blue', 'green'] as $group){
					if(array_key_exists($group, $t_entries)){
						foreach($t_entries[$group] as $entry){
							$row_tru = $row_tru . tag('div', tag('span', get_orb($entry['group']), 'class="group-tag"') . get_icon($entry['dungeon_name']), 'class="float"');
						}
					}
				}
				$tbl_tr = $tbl_tr . tag('tr', tag('td', '', 'class="time-remain" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
				$tbl_tu = $tbl_tu . tag('tr', tag('td', '', 'class="time-until" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
			}
		}

		$server_out .= tag('div', tag('table', $tbl_gs), 'class="group"');
		$server_out .= tag('div', tag('table', $tbl_ts), 'class="schedule"');
		$server_out .= tag('table', $tbl_tr . $tbl_tu, 'class="next"');
		$server_out = tag('div', $server_out, "class=\"$server\"");

		$out = $out . $server_out;
	}
	return $out;
}
$na_url = 'https://storage.googleapis.com/mirubot/protic/paddata/processed/na_bonuses.json?' . time();
$jp_url = 'https://storage.googleapis.com/mirubot/protic/paddata/processed/jp_bonuses.json?' . time();
echo get_guerrilla_tables($na_url, $jp_url);
?>
</body>
</html>
