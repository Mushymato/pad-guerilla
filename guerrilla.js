function fmtDay(d) {
	return moment(d * 1000).format('M/DD');
}
function fmtHour(d){
	return moment(d * 1000).format('HH:mm');
}
function fmtDate(d){
	return moment(d * 1000).format('M/DD HH:mm');
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
window.onload=function(){
	function addRow(id, row) {
		$(id+' > tbody:last-child').append(row);
	};
	function tr(text) {
		return '<tr>' + text + '</tr>';
	}
	function td(text) {
		return '<td>' + text + '</td>';
	}
	var icon = {};
	function getIcon(dungeonName) {
		if(dungeonName.charAt(0) === '$'){
			var tmp = dungeonName.split('$');
			dungeonName = tmp[2];
		}
		var w = "50";
		if(icon[dungeonName] != null){
			var dungeonLink = (icon[dungeonName]["d"].length > 0) ? icon['dungeonURL'] + icon[dungeonName]["d"] : "#";
			return "<a href=\"" + dungeonLink + "\" title=\""+dungeonName+"\"><img src=\"" + icon['iconURL'] + icon[dungeonName]["i"] + "\" width="+w+" height="+w+"/></a>";
		}else{
			return "<img src=\""+icon['iconURL']+"4014.png\" title=\""+dungeonName+"\" width="+w+" height="+w+"/>";
		}
	}
	
	// NA is UTC-8, JP is UTC+9
	var dayStart = {
		'NA': moment().utcOffset(-8).startOf('day').unix(),
		'JP': moment().utcOffset(9).startOf('day').unix()
	};
	var dayEnd = {
		'NA': moment().utcOffset(-8).endOf('day').unix(),
		'JP': moment().utcOffset(9).endOf('day').unix()
	};
	
	var timeGrouped = {'NA': new Map(), 'JP': new Map()};
	var nextTime = {'NA': 0, 'JP': 0};
	var activeTime = {'NA': 0, 'JP': 0};
	
	function loadGroupData(data) {
		var items = data['items'];
		var namedItems = {};
		for (var x of items) {
			if(x['server'] === this.server){
				if(dayStart[x['server']] < x['start_timestamp'] && dayEnd[x['server']] > x['start_timestamp']){
					var name = x['dungeon_name'];
					var group = x['group'];
					if (!(name in namedItems)) {
						namedItems[name] = new Map();
					}
					if (!(namedItems[name].has(group))) {
						namedItems[name].set(group, []);
					}
					namedItems[name].get(group).push(x);
				}
			}
		}
		for (var name in namedItems) {
			var groupedItems = namedItems[name];
			var firstItem = groupedItems.values().next().value[0];
			var server = firstItem['server'];
			var row = td(getIcon(name));
			for (var g of ['A', 'B', 'C', 'D', 'E']) {
				if (!(groupedItems.has(g))) {
					row += td('');
				}else{
					var times = '';
					var tdTag = '<td>';
					for(var item of groupedItems.get(g)){
						var now = moment().unix() ;
						if(now > item['start_timestamp'] && now < item['end_timestamp']){
							times += '<div class=\"highlight\">' + fmtDate(item['start_timestamp']) + '</div>';
							tdTag = '<td class=\"highlight\">';
						}else{
							times += '<div>' + fmtDate(item['start_timestamp']) + '</div>';
						}
					}
					row += tdTag + times + '</td>';
				}
			}
			addRow('#group'+server,tr(row));
		}
	}
	function loadScheduleData(data) {
		var items = data['items'];
		
		for (var i of items) {
			var sts = i['start_timestamp'];
			if(!timeGrouped[i['server']].has(sts)){
				timeGrouped[i['server']].set(sts, []);
			}
			timeGrouped[i['server']].get(sts).push(i);
		}

		for (var server in timeGrouped){
			timeGrouped[server] = new Map([...timeGrouped[server].entries()].sort());
			timeGrouped[server].forEach(function(value, key, map) {
				
				var now = moment().unix() ;
				if(now < value[0]['end_timestamp']){
					var row = td(fmtDate(key));
					value.sort(function(a,b){
						return a['group'].toString().localeCompare(b['group'].toString());
					});
					
					/*for (var dungeon of value){
						row += td(dungeon['group'] + ' : ' +getIcon(dungeon['dungeon_name']));
					}*/
					
					var g = {'A':0, 'B':1, 'C':2, 'D':3, 'E':4};
					var current = 0;
					for (var i of value){
						while(current < g[i['group']]){
							row += td('');
							current += 1;
						}
						var icon = getIcon(i['dungeon_name']);
						if(current > g[i['group']]){
							row = row.substring(0,row.length - 5) + '</br>' + icon + '</td>';
						}else{
							row += td(icon);
							current += 1;
						}
					}
					while(current < 5){
						row += td('');
						current += 1;
					}
					
					if(now > key * 1000){
						row = '<tr class=\"highlight\">' + row + '</tr>';
					}else{
						row = tr(row);
					}
					addRow('#schedule'+server,row);
				}
			});
			nextTime[server] = timeGrouped[server].values().next().value[0]['start_timestamp'];
		}
	}
	function populate() {
		$.getJSON('./guerrilla_icon.json').done(function(data){
			icon = data;
			//$.getJSON('https://storage.googleapis.com/mirubot/paddata/merged/guerrilla_data.json').done(loadSortedData);
			$.getJSON({url:'./gd_daily_na.json', server:'NA', cache: false}).done(loadGroupData);
			$.getJSON({url:'./gd_daily_jp.json', server:'JP', cache: false}).done(loadGroupData);
			$.getJSON({url:'./gd_hourly.json', cache: false}).done(loadScheduleData);
		});
	}
	function cdUpdate() {
		var now = moment().unix();
		for (var server in timeGrouped){
			if(nextTime[server] - now < 0){
				var next = 0;
				for (let [k, v] of timeGrouped[server]) {
					if(k > now){
						next = k;
						break;
					}
				}
				nextTime[server] = next;
			}
			if(activeTime[server] - now < 0){
				var next = 0;
				for (let [k, v] of timeGrouped[server]) {
					if(k < now && v[0]['end_timestamp'] > now){
						next = k;
						break;
					}
				}
				activeTime[server] = next;
			}
			var clock;
			var icon;
			if(nextTime[server] > 0){
				var distance = nextTime[server] - now;
				var hours = Math.floor((distance % (60 * 60 * 24)) / (60 * 60));
				var minutes = Math.floor((distance % (60 * 60)) / (60));
				clock = hours + "h " + minutes + "m ";
				icon = '<td>';
				for (var dungeon of timeGrouped[server].get(nextTime[server])){
					icon += '<span class="abcde">' + dungeon['group'] + '</span>' +getIcon(dungeon['dungeon_name']) + '</td><td>';
				}
				icon.substring(0, icon.length-4);
			}else{
				clock = 0;
				icon = "None";
			}
			$('#nextTime'+server).html(clock);
			$('#nextDungeon'+server).html(icon);
			
			if(activeTime[server] > 0){
				var endtime = timeGrouped[server].get(activeTime[server])[0]['end_timestamp'];
				var distance = endtime - now;
				var hours = Math.floor((distance % (60 * 60 * 24)) / (60 * 60));
				var minutes = Math.floor((distance % (60 * 60)) / (60));
				clock = hours + "h " + minutes + "m";
				icon = '<td>';
				for (var dungeon of timeGrouped[server].get(activeTime[server])){
					icon += '<span class="abcde">' + dungeon['group'] + '</span>' +getIcon(dungeon['dungeon_name']) + '</td><td>';
				}
				icon.substring(0, icon.length-4);
			}else{
				clock = "--h --m";;
				icon = "None";
			}
			$('#activeTime'+server).html(clock);
			$('#activeDungeon'+server).html(icon);
		}
	}
	function cdUpdateRepeat(){
		cdUpdate();
		setTimeout(cdUpdateRepeat, 60000);
	}
	populate();
	if(window.localStorage.getItem('region') === null){
		window.localStorage.setItem('region', 'JP');
	}
	if(window.localStorage.getItem('mode') === null){
		window.localStorage.setItem('mode', 'group');
	}
	refreshSetting();
	window.setTimeout(function(){
		setTimeout(cdUpdate, 500);
		setTimeout(cdUpdateRepeat, (60 - moment().second()) * 1000);
	}, 1000);
}
