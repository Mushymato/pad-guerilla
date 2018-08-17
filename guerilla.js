	function refreshSetting() {
		if(window.localStorage.getItem('region') === 'NA'){
			$('.NA').css('display', 'table');
			$('.JP').css('display', 'none');
		}else{
			$('.NA').css('display', 'none');
			$('.JP').css('display', 'table');
		}
		var m = window.localStorage.getItem('mode');
		if(m === 'group'){
			$('.group').css('display', 'block');
			$('.schedule').css('display', 'none');
		}else if (m === 'schedule'){
			$('.group').css('display', 'none');
			$('.schedule').css('display', 'block');
		}else if (m === 'timeto'){
			
		}
	}
	function switchRegion(){
		if(window.localStorage.getItem('region') === 'NA'){
			window.localStorage.setItem('region', 'JP');
		}else{
			window.localStorage.setItem('region', 'NA');
		}
		refreshSetting();
	}
	function switchMode(){
		if(window.localStorage.getItem('mode') === 'group'){
			window.localStorage.setItem('mode', 'schedule');
		}else{
			window.localStorage.setItem('mode', 'group');
		}
		refreshSetting();
	}
	function pickMode(mode){
		indow.localStorage.setItem('mode', mode);
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
		function fmtDate(d) {
			var s = d.getMonth() + '/' + d.getDate() + ' ' + ("0" + d.getHours()).slice(-2) + ':' + ("0" + d.getMinutes()).slice(-2);
			return s;
		}
		var icon = {};
		function getIcon(dungeon_name) {
			var w = "50";
			if(icon[dungeon_name] != null){
				return "<a href=\"" + icon['dungeonURL'] + icon[dungeon_name]["d"] + "\" title=\""+dungeon_name+"\"><img src=\"" + icon['iconURL'] + icon[dungeon_name]["i"] + "\" width="+w+" height="+w+"/></a>";
			}else{
				return "<img src=\""+icon['iconURL']+"4014.png\" title=\""+dungeon_name+"\" width="+w+" height="+w+"/>";
			}
		}
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
			
			var timeGroupedNA = new Map();
			var timeGroupedJP = new Map();
			var timeGroupedItems;
			for (var i of items) {
				var sts = i['start_timestamp'];
				if(i['server'] === 'NA'){
					timeGroupedItems = timeGroupedNA;
				}else{
					timeGroupedItems = timeGroupedJP;
				}
				if(!timeGroupedItems.has(sts)){
					timeGroupedItems.set(sts, []);
				}
				timeGroupedItems.get(sts).push(i);
			}

			for (var timeGroupedItems of [timeGroupedNA, timeGroupedJP]){
				timeGroupedItems = new Map([...timeGroupedItems.entries()].sort());
				timeGroupedItems.forEach(function(value, key, map) {
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
					addRow('#schedule'+value[0]['server'],tr(row));
				});
			}
		}
		function populate() {
			$.getJSON('./guerilla_icon.json').done(function(data){
				icon = data;
			});
			$.getJSON('https://storage.googleapis.com/mirubot/paddata/merged/guerrilla_data.json').done(loadSortedData);
		}

		populate();
		if(window.localStorage.getItem('region') === null){
			window.localStorage.setItem('region', 'JP');
		}
		if(window.localStorage.getItem('mode') === null){
			window.localStorage.setItem('mode', 'group');
		}
		refreshSetting();
	}