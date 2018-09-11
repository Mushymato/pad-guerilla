<html>
<title>Guerilla Dungeons</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js" type="text/javascript"></script>
<script type="text/javascript">
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
window.onload=function(){
	refreshSetting();
	console.log();
}
</script>
<style>
.NA{
	display:block;
}
.JP{
	display:none;
}
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
</style>

</head>
<body>
<?php
ini_set("allow_url_fopen", 1);
$data = file_get_contents("https://storage.googleapis.com/mirubot/paddata/merged/guerrilla_data.json");
$json = json_decode($data, true);
echo $json['items'][0]['dungeon_name'];
?>
	<!--<p>TIMES ARE LOCAL TO YOUR BROWSER</p>
	<button onclick="switchRegion();">Switch Region: <span id="region"></span></button>
	<button onclick="pickMode('group');">By Group</button>
	<button onclick="pickMode('schedule');">By Time</button>
	<button onclick="pickMode('next');">By Countdown</button>
	<div class="NA">
		<table id="groupNA" class="group">
			<tbody>
				<tr>
					<td>Dungeon</td>
					<td>A</td>
					<td>B</td>
					<td>C</td>
					<td>D</td>
					<td>E</td>
				</tr>
		</table>
		<table id="scheduleNA" class="schedule">
			<tbody>
				<tr>
					<td>Time</td>
					<td>A</td>
					<td>B</td>
					<td>C</td>
					<td>D</td>
					<td>E</td>
				</tr>
		</table>
		<table id="nextNA" class="next">
			<tbody>
				<tr>
					<td>Time Remaining</td>
					<td>Dungeon</td>
				</tr>
				<tr>
					<td id="activeTimeNA"></td>
					<td id="activeDungeonNA"></td>
				</tr>
				<tr>
					<td>Time Until</td>
					<td>Dungeon</td>
				</tr>
				<tr>
					<td id="nextTimeNA"></td>
					<td id="nextDungeonNA"></td>
				</tr>
		</table>
	</div>

	<div class="JP">
		<table id="groupJP" class="group">
			<tbody>
				<tr>
					<td>Dungeon</td>
					<td>A</td>
					<td>B</td>
					<td>C</td>
					<td>D</td>
					<td>E</td>
				</tr>
		</table>
		<table id="scheduleJP" class="schedule">
			<tbody>
				<tr>
					<td>Time</td>
					<td>A</td>
					<td>B</td>
					<td>C</td>
					<td>D</td>
					<td>E</td>
				</tr>
		</table>
		<table id="nextJP" class="next">
			<tbody>
				<tr>
					<td>Time Remaining</td>
					<td>Dungeon</td>
				</tr>
				<tr>
					<td id="activeTimeJP"></td>
					<td id="activeDungeonJP"></td>
				</tr>
				<tr>
					<td>Time Until</td>
					<td>Dungeon</td>
				</tr>
				<tr>
					<td id="nextTimeJP"></td>
					<td id="nextDungeonJP"></td>
				</tr>
		</table>
	</div>-->
</body>
</html>
