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
.abcde{
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
<p>TIMES ARE LOCAL TO YOUR BROWSER</p><button onclick="switchRegion();">Switch Region: <span id="region"></span></button><button onclick="pickMode('group');">By Group</button><button onclick="pickMode('schedule');">By Time</button><button onclick="pickMode('next');">By Countdown</button>
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
	$dungeon_url = $icon['dungeonURL'] . $icon[$dungeon_name]['d'];
	return tag('a', "<img src=\"$icon_url\" class=\"dungeon-icon\">", "href=\"$dungeon_url\" title=\"$dungeon_name\"");
}
function get_orb($orb){
	$orb_id = ['RED' => '1', 'BLUE' => '2', 'GREEN' => '3'];
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
	$is_starter_grouping: flag for using RGB starter group over player groups on the given server
	$except_dungeons: dungeon that use the other grouping mode (i.e. if starter group flag is false, this dungeon uses RGB groups, if starter group flag is true, this dungeon uses player groups)
*/
function get_guerrilla_tables($url_na, $url_jp, $is_starter_grouping = array('NA' => false, 'JP' => false), $except_dungeons = array()){
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
	
	$gd = array();
	$gd['NA'] = get_json($url_na)['items'];
	$gd['JP'] = get_json($url_jp)['items'];
	foreach($gd as $f => $array){
		foreach($array as $value){
			if($value['server'] == $f){
				
				// starter group sorting
				// A/B/E are red starter, C is green, and D is blue
				// when in starter group mode, B/E data is discarded
				$is_starter = $is_starter_grouping[$value['server']];
				$except = in_array($value['dungeon_name'], $except_dungeons);
				if(($is_starter && !$except) || (!$is_starter && $except)){
					if($value['group'] == 'A'){
						$value['group'] = 'RED';
					}else if($value['group'] == 'D'){
						$value['group'] = 'BLUE';
					}else if($value['group'] == 'C'){
						$value['group'] = 'GREEN';
					}else if($value['group'] == 'B' || $value['group'] == 'E'){
						continue;
					}
				}
				
				if($value['start_timestamp'] >= $day[$value['server']]['start'] && $value['end_timestamp'] <= $day[$value['server']]['end']){
					$by_dungeon_group[$value['server']][$value['dungeon_name']][$value['group']][] = $value;
				}
				$by_time[$value['server']][$value['start_timestamp']][$value['group']][] = $value;
				$start_end[$value['start_timestamp']] = $value['end_timestamp'];
			}
		}
	}

	$out = '';
	foreach(['JP', 'NA'] as $server){
		$server_out = '';
		$tbl_g = '<tr><td>Dungeon</td><td>A</td><td>B</td><td>C</td><td>D</td><td>E</td></tr>';
		$tbl_gs= '<tr><td>Dungeon</td><td>' . get_orb('RED') . '</td><td>' . get_orb('BLUE') . '</td><td>' . get_orb('GREEN') . '</td></tr>';
		$has_player_groups = false;
		$has_starter_groups = false;
		foreach($by_dungeon_group[$server] as $dungeon_name => $d_entries){
			$player_row = get_table_group_rows($dungeon_name, $d_entries, ['A', 'B', 'C', 'D', 'E']);
			$has_player_groups = $has_player_groups || $player_row != '';
			$tbl_g = $tbl_g . $player_row;
			$starter_row = get_table_group_rows($dungeon_name, $d_entries, ['RED', 'BLUE', 'GREEN']);
			$tbl_gs = $tbl_gs . $starter_row;
			$has_starter_groups = $has_starter_groups || $starter_row != '';
		}
		$group_div = $has_player_groups ? tag('table', $tbl_g) : '';
		$group_div = $has_starter_groups ? $group_div . tag('table', $tbl_gs) : $group_div;
		$server_out = $server_out . tag('div', $group_div, 'class="group"');
		
		ksort($by_time[$server]);
		$tbl_t = '<tr><td>Time</td><td>A</td><td>B</td><td>C</td><td>D</td><td>E</td></tr>';
		$tbl_ts = '<tr><td>Time</td><td>' . get_orb('RED') . '</td><td>' . get_orb('BLUE') . '</td><td>' . get_orb('GREEN') . '</td></tr>';
		$has_player_groups = false;
		$has_starter_groups = false;
		$tbl_tr = '<tr><td>Time Remaining</td><td>Dungeon</td></tr><tr class="tr-none"><td>--h --m</td><td>None</td></tr>';
		$tbl_tu = '<tr><td>Time Until</td><td>Dungeon</td></tr><tr class="tu-none"><td>--h --m</td><td>None</td></tr>';
		foreach($by_time[$server] as $start_time => $t_entries){
			if($start_end[$start_time] >= time()){
				$player_row = get_table_time_rows($start_time, $t_entries, $start_end, ['A', 'B', 'C', 'D', 'E']);
				$has_player_groups = $has_player_groups || $player_row != '';
				$tbl_t = $tbl_t . $player_row;
				$starter_row = get_table_time_rows($start_time, $t_entries, $start_end, ['RED', 'BLUE', 'GREEN']);
				$tbl_ts = $tbl_ts . $starter_row;
				$has_starter_groups = $has_starter_groups || $starter_row != '';
				
				$row_tru = '';
				foreach(['A', 'B', 'C', 'D', 'E', 'RED', 'BLUE', 'GREEN'] as $group){
					if(array_key_exists($group, $t_entries)){
						foreach($t_entries[$group] as $entry){
							$row_tru = $row_tru . tag('div', tag('span', get_orb($entry['group']), 'class="abcde"') . get_icon($entry['dungeon_name']), 'class="float"');
						}
					}
				}
				$tbl_tr = $tbl_tr . tag('tr', tag('td', '', 'class="time-remain" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
				$tbl_tu = $tbl_tu . tag('tr', tag('td', '', 'class="time-until" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
			}
		}
		
		$group_div = $has_player_groups ? tag('table', $tbl_t) : '';
		$group_div = $has_starter_groups ? $group_div . tag('table', $tbl_ts) : $group_div;
		$server_out = $server_out . tag('div', $group_div, 'class="schedule"');

		$server_out = $server_out . tag('table', $tbl_tr . $tbl_tu, 'class="next"');
		
		$server_out = tag('div', $server_out, "class=\"$server\"");
		$out = $out . $server_out;
	}
	return $out;
}
$miru_url = 'https://storage.googleapis.com/mirubot/paddata/merged/guerrilla_data.json?' . time();
$local_url = './gd_override.json';
echo get_guerrilla_tables($miru_url, $miru_url, array('NA' => false, 'JP' => true));
?>
</body>
</html>
