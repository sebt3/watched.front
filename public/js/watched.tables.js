/////////////////////////////////////////////////////////////////////////////////////////////
// wdTableChart
function htmlDecode(text) {
	var parser = new DOMParser;
	var dom = parser.parseFromString('<!doctype html><body>' + text,'text/html');
	return dom.body.textContent;
}

function wdTableBodyChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdBaseComponant(),
		keys	= [],
		heads, rows;
	chart.dispatch.on("init.wdTableChart", function() {
		heads = chart.root().selectAll('thead th.sortable').on('click', function(d,i) {
			if(typeof rows == "undefined") return;
			chart.root().selectAll('thead th.sortable').selectAll('i')
				.attr('class', 'fa fa-sort pull-right');
			if (typeof this.sortType == "undefined")	this.sortType = "desc";
			else if (this.sortType == "asc")		this.sortType = "desc";
			else						this.sortType = "asc";
			if (this.sortType == "asc") {
				d3.select(this).select('i').attr('class', 'fa fa-sort-up pull-right');
				rows.sort(function (a,b) {
					if (typeof a[keys[i]] == "object")
						return a[keys[i]].text>b[keys[i]].text;
					return a[keys[i]]>b[keys[i]];
				});
			} else {
				d3.select(this).select('i').attr('class', 'fa fa-sort-down pull-right');
				rows.sort(function (a,b) {
					if (typeof a[keys[i]] == "object")
						return a[keys[i]].text<b[keys[i]].text;
					return a[keys[i]]<b[keys[i]];
				});
			}
		}).append('i').attr('class', 'fa fa-sort pull-right');
	});
	chart.dispatch.on("renderUpdate.wdTableChart", function() {
		var update = chart.root().select('tbody').selectAll('tr').data(chart.data());
		update.exit().remove();
		if(typeof chart.data()[0] == "undefined") return;
		keys = Object.keys(chart.data()[0]);
		rows = update.enter().append('tr')/*.each(function(d){
			if(typeof d.rowProperties == "object" && d.rowProperties != null) {
			}
		});*/
		rows.selectAll('td').data(function (d, i) {
			var j=0, ret=[];
			for (var k in d) {
				if(!d.hasOwnProperty(k)||k=="rowProperties") continue;
				ret.push({ id: ++j, rowid: i, name: k, value: d[k] })
			}
			return ret;
		}).enter().append('td').each(function(d,i) {
			if (d.name =="actions") {
				d3.select(this).attr('class', 'text-right').selectAll('a').data(d.value).enter()
				  .append('a').each(function(p,j) {
					if(typeof p.target != "undefined")
						d3.select(this).attr('data-toggle', 'modal').attr('data-target', p.target)
							.append('i').attr('class', p.icon);
					else
						d3.select(this).attr('href', p.url)
							.append('i').attr('class', p.icon);
				}).append('span').text(' ');
			} else if (typeof d.value == "object" && d.value != null) {
				if (typeof d.value.color != "undefined")
					d3.select(this).attr("class", d.value.color);
				if (typeof d.value.icon  != "undefined")
					d3.select(this).append('i').attr('class', d.value.icon)
				if (typeof d.value.url  != "undefined") {
					var a = d3.select(this).append('a')
						.attr('href', d.value.url)
						.text(' '+htmlDecode(d.value.text));
					if (typeof d.value.color != "undefined")
						a.attr('class', d.value.color);
				} else {
					d3.select(this).append('span')
						.text(' '+htmlDecode(d.value.text));
				}
			} else if (typeof d.value != "undefined")
				d3.select(this).text( htmlDecode(d.value) )
		});
	});
	return chart;
}
function watchedTable(id, data) {
	var chart = wdTableBodyChart().data(data);
	d3.select("#"+id).call(chart);
	return chart;
}
