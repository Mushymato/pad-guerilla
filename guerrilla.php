<html>
<title>Guerilla Dungeons</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js" type="text/javascript"></script>
<script type="text/javascript">
function fmtDate(d){
	return moment(d * 1000).format('M/DD HH:mm');
}
function fmtCd(d){
	var hours = Math.floor((d % (60 * 60 * 24)) / (60 * 60));
	var minutes = Math.floor((d % (60 * 60)) / (60));
	return hours + 'h ' + minutes + 'm';
}
function refreshSetting() {
	$('#region').html(window.localStorage.getItem('region'));
	if(window.localStorage.getItem('region') === 'NA'){
		$('.NA').css('display', 'block');
		$('.JP').css('display', 'none');
	}else{
		$('.NA').css('display', 'none');
		$('.JP').css('display', 'block');
	}
	var modes = ['.group', '.schedule', '.next'];
	for (var m of modes){
		$(m).css('display', 'none');
	}
	$('.'+window.localStorage.getItem('mode')).css('display', 'table');
}
function switchRegion(){
	if(window.localStorage.getItem('region') === 'NA'){
		window.localStorage.setItem('region', 'JP');
	}else{
		window.localStorage.setItem('region', 'NA');
	}
	refreshSetting();
}
function pickMode(mode){
	window.localStorage.setItem('mode', mode);
	refreshSetting();
}
function toLocalTime(){
	$(".timestamp").each(function(index) {
		$(this).html(fmtDate(parseInt($(this).attr('data-timestamp'))));
	});
}
function updateTimediff(){
	var now = moment().unix();
	for(var region of ['NA', 'JP']){
		var found = false;
		$('.' + region + ' .time-remain').each(function(index) {
			var ts = parseInt($(this).attr('data-timestart'));
			var te = parseInt($(this).attr('data-timeend'));
			if(ts <= now && te >= now){
				$(this).html(fmtCd(te - now));
				$(this).parent().css('display', 'table-row');
				found = true;
			}else{
				$(this).parent().css('display', 'none');
			}
		});
		if(found){
			$('.' + region + ' .tr-none').css('display', 'none');
		}else{
			$('.' + region + ' .tr-none').css('display', 'table-row');
		}
		found = false;
		$('.' + region + ' .time-until').each(function(index) {
			var ts = parseInt($(this).attr('data-timestart'));
			if(ts > now && !found){
				$(this).html(fmtCd(ts - now));
				$(this).parent().css('display', 'table-row');
				found = true;
			}else{
				$(this).parent().css('display', 'none');
			}
		});
		if(found){
			$('.' + region + ' .tu-none').css('display', 'none');
		}else{
			$('.' + region + ' .tu-none').css('display', 'table-row');
		}
	}
}
window.onload=function(){
	refreshSetting();
	toLocalTime();
	updateTimediff();
	setTimeout(function(){
		updateTimediff();
		setTimeout(updateTimediff, 60000);
	}, 60000 - moment().millisecond());
}
</script>
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
	global $icon;
	$icon_url = $icon['iconURL'] . $icon[$dungeon_name]['i'];
	$dungeon_url = $icon['dungeonURL'] . $icon[$dungeon_name]['i'];
	return tag('a', "<img src=\"$icon_url\" class=\"dungeon-icon\">", "href=\"$dungeon_url\" title=\"$dungeon_name\"");
}
function get_buttons(){
	return "<p>TIMES ARE LOCAL TO YOUR BROWSER</p><button onclick=\"switchRegion();\">Switch Region: <span id=\"region\"></span></button><button onclick=\"pickMode('group');\">By Group</button><button onclick=\"pickMode('schedule');\">By Time</button><button onclick=\"pickMode('next');updateTimediff();\">By Countdown</button>";
}
function get_tables(){
	$by_dungeon_group = array('JP' => array(), 'NA' => array());
	$by_time = array('JP' => array(), 'NA' => array());
	$start_end = array();
	$tform = 'm/d H:i e';
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
	$gd = get_json('https://storage.googleapis.com/mirubot/paddata/merged/guerrilla_data.json?' . time())['items'];
	foreach($gd as $value){
		if($value['start_timestamp'] >= $day[$value['server']]['start'] && $value['end_timestamp'] <= $day[$value['server']]['end']){
			$by_dungeon_group[$value['server']][$value['dungeon_name']][$value['group']][] = $value;
		}
		$by_time[$value['server']][$value['start_timestamp']][$value['group']][] = $value;
		$start_end[$value['start_timestamp']] = $value['end_timestamp'];
	}
	
	//caching ver, untested
	/*$gd = array();
	$gd['NA'] = get_json('./gd_daily_na.json)['items'];
	$gd['JP'] = get_json('./gd_daily_jp.json)['items'];
	foreach($gd as $f => $array){
		foreach($array as $value){
			if($value['server'] == $f){
				if($value['start_timestamp'] >= $day[$value['server']]['start'] && $value['end_timestamp'] <= $day[$value['server']]['end']){
					$by_dungeon_group[$value['server']][$value['dungeon_name']][$value['group']][] = $value;
				}
				$by_time[$value['server']][$value['start_timestamp']][$value['group']][] = $value;
				$start_end[$value['start_timestamp']] = $value['end_timestamp'];
			}
		}
	}*/

	$out = '';
	foreach(['JP', 'NA'] as $server){
		$server_out = '';
		$tbl = '<tr><td>Dungeon</td><td>A</td><td>B</td><td>C</td><td>D</td><td>E</td></tr>';
		foreach($by_dungeon_group[$server] as $dungeon_name => $d_entries){
			$row = tag('td', get_icon($dungeon_name));
			foreach(['A', 'B', 'C', 'D', 'E'] as $group){
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
				}else{
					$row = $row . tag('td', '');
				}
			}
			$row = tag('tr', $row);
			$tbl = $tbl . $row;
		}
		$server_out = $server_out . tag('table', $tbl, 'class="group"');
		
		ksort($by_time[$server]);
		$tbl = '<tr><td>Time</td><td>A</td><td>B</td><td>C</td><td>D</td><td>E</td></tr>';
		$tbl_tr = '<tr><td>Time Remaining</td><td>Dungeon</td></tr><tr class="tr-none"><td>--h --m</td><td>None</td></tr>';
		$tbl_tu = '<tr><td>Time Until</td><td>Dungeon</td></tr><tr class="tu-none"><td>--h --m</td><td>None</td></tr>';
		foreach($by_time[$server] as $start_time => $t_entries){
			if($start_end[$start_time] >= time()){
				$row = tag('td', date($tform, $start_time), 'class="timestamp" data-timestamp="' . (String) $start_time . '"');
				$row_tru = '';
				foreach(['A', 'B', 'C', 'D', 'E'] as $group){
					if(array_key_exists($group, $t_entries)){
						$cells = array();
						foreach($t_entries[$group] as $entry){
							$cells[$entry['dungeon_name']] = tag('div', get_icon($entry['dungeon_name']));
							$row_tru = $row_tru . tag('div', tag('span', $entry['group'], 'class="abcde"') . get_icon($entry['dungeon_name']), 'class="float"');
						}
						$row = $row . tag('td', implode($cells));
					}else{
						$row = $row . tag('td', '');
					}
				}
				if($start_time <= time() && $start_end[$start_time] >= time()){
					$tbl = $tbl . tag('tr', $row, 'class="highlight"');
				}else{
					$tbl = $tbl . tag('tr', $row);
				}
				$tbl_tr = $tbl_tr . tag('tr', tag('td', '', 'class="time-remain" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
				$tbl_tu = $tbl_tu . tag('tr', tag('td', '', 'class="time-until" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
			}
		}
		$server_out = $server_out . tag('table', $tbl, 'class="schedule"');
		$server_out = $server_out . tag('table', $tbl_tr . $tbl_tu, 'class="next"');
		
		$server_out = tag('div', $server_out, "class=\"$server\"");
		$out = $out . $server_out;
	}
	return $out;
}

echo get_buttons();
echo get_tables();

?>
</body>
</html>
