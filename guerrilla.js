function fmtDate(d){
	return moment(d * 1000).tz(window.localStorage.getItem('timezone')).format('M/DD HH:mm z');
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
function switchTimezone(){
	if(window.localStorage.getItem('timezone') === 'Asia/Tokyo'){
		window.localStorage.setItem('timezone', moment.tz.guess());
	}else{
		window.localStorage.setItem('timezone', 'Asia/Tokyo');
	}
	refreshTime();
}
function pickMode(mode){
	window.localStorage.setItem('mode', mode);
	refreshSetting();
}
function refreshTime(){
	$('#timezone').html(moment.tz(window.localStorage.getItem('timezone')).format('z'));
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
	if(window.localStorage.getItem('region') === null){
		window.localStorage.setItem('region', 'JP');
	}
	if(window.localStorage.getItem('mode') === null){
		window.localStorage.setItem('mode', 'group');
	}
	if(window.localStorage.getItem('timezone') === null){
		window.localStorage.setItem('timezone', moment.tz.guess());
	}
	refreshSetting();
	refreshTime();
	updateTimediff();
	setTimeout(function(){
		updateTimediff();
		setInterval(updateTimediff, 60000);
	}, 60000 - moment().millisecond());
}