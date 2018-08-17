	function fmtDate(d) {
		var s = d.getMonth() + '/' + d.getDate() + ' ' + ("0" + d.getHours()).slice(-2) + ':' + ("0" + d.getMinutes()).slice(-2);
		return s;
	}
	
	function refreshSetting() {
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
			var w = "50";
			if(icon[dungeonName] != null){
				var dungeonLink = (icon[dungeonName]["d"].length > 0) ? icon['dungeonURL'] + icon[dungeonName]["d"] : "#";
				return "<a href=\"" + dungeonLink + "\" title=\""+dungeonName+"\"><img src=\"" + icon['iconURL'] + icon[dungeonName]["i"] + "\" width="+w+" height="+w+"/></a>";
			}else{
				return "<img src=\""+icon['iconURL']+"4014.png\" title=\""+dungeonName+"\" width="+w+" height="+w+"/>";
			}
		}
		var timeGrouped = {'NA': new Map(), 'JP': new Map()};
		var nextTime = {'NA': 0, 'JP': 0};
		var activeTime = {'NA': 0, 'JP': 0};
		function loadSortedData(data) {
			var items = data['items'];
			
			var namedItems = {};
			for (var x of items) {
				var name = x['dungeon_name'];
				var group = x['group'];
				if (!(name in namedItems)) {
					namedItems[name] = new Map();
				}
				namedItems[name].set(group, x);
			}
			for (var name in namedItems) {
				var groupedItems = namedItems[name];
				var firstItem = groupedItems.values().next().value;
				var server = firstItem['server'];
				var row = td(getIcon(name));
				for (var g of ['A', 'B', 'C', 'D', 'E']) {
					var item = groupedItems.get(g, new Map());
					if (item && 'start_timestamp' in item) {
						row += td(fmtDate(new Date(item['start_timestamp'] * 1000)));
					} else {
						row += td('');
					}
				}
				addRow('#group'+server,tr(row));
			}
			
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
					var row = td(fmtDate(new Date(key * 1000)));
					value.sort(function(a,b){
						return a['group'].toString().localeCompare(b['group'].toString());
					});
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
					addRow('#schedule'+server,tr(row));
					nextTime[server] = timeGrouped[server].values().next().value[0]['start_timestamp'];
				});
			}
		}
		function populate() {
			$.getJSON('./guerrilla_icon.json').done(function(data){
				icon = data;
			});
			$.getJSON('https://storage.googleapis.com/mirubot/paddata/merged/guerrilla_data.json').done(loadSortedData);
			//$.getJSON('./guerrilla_data.json').done(loadSortedData);
		}
		function cdUpdate() {
			var now = new Date().getTime();
			for (var server in timeGrouped){
				console.log(nextTime[server]);
				if(nextTime[server] * 1000 - now < 0 || nextTime[server] == 0){
					var next = 0;
					for (var val of timeGrouped[server]) {
						if(val[0] * 1000 > now && next == 0){
							next = val[0];
							break;
						}
					}
					activeTime[server] = nextTime[server];
					nextTime[server] = next;
				}
				if(activeTime[server] * 1000 - now < 0 || activeTime[server] == 0){
					activeTime[server] = 0;
				}
				console.log(nextTime[server]);
				var clockNext;
				var clockActive;
				var iconNext;
				var iconActive;
				if(nextTime[server] > 0){
					var distance = nextTime[server] * 1000 - now;
					var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
					var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
					clockNext = hours + "h " + minutes + "m ";
					iconNext = '';
					for (var dungeon of timeGrouped[server].get(nextTime[server])){
						iconNext += dungeon['group'] + ' : ' +getIcon(dungeon['dungeon_name']);
					}
					console.log(clockNext);
					console.log(iconNext);
				}else{
					clockNext = 0;
					iconNext = "None";
				}
				$('#nextTime'+server).html(clockNext);
				$('#nextDungeon'+server).html(iconNext);
				
				if(activeTime[server] > 0){
					var distance = activeTime[server] * 1000 - now;
					var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
					var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
					clockActive = hours + "h " + minutes + "m";
					iconActive = '';
					for (var dungeon of timeGrouped[server].get(activeTime[server])){
						iconActive += dungeon['group'] + ' : ' +getIcon(dungeon['dungeon_name']);
					}
					$('.active').css('display', 'table');
				}else{
					clockActive = "--h --m";;
					iconActive = "None";
					$('.active').css('display', 'none');
				}
				$('#activeTime'+server).html(clockActive);
				$('#activeDungeon'+server).html(iconActive);
			}
			
			setTimeout(cdUpdate, 60000);
		}
		populate();
		if(window.localStorage.getItem('region') === null){
			window.localStorage.setItem('region', 'JP');
		}
		if(window.localStorage.getItem('mode') === null){
			window.localStorage.setItem('mode', 'group');
		}
		// var cd = setInterval(cdUpdate, 60000);		
		refreshSetting();
		window.setTimeout(cdUpdate,100);
	}