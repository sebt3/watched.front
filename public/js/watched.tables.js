/////////////////////////////////////////////////////////////////////////////////////////////
// wdTableChart
function wdTableChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdBaseComponant(),
		keys	= [],
		heads, rows;
	chart.dispatch.on("init.wdTableChart", function() {
		heads = chart.root().selectAll('thead th.sortable').on('click', function(d,i) {
			chart.root().selectAll('thead th.sortable').selectAll('i')
				.attr('class', 'fa fa-sort pull-right');
			if (typeof this.sortType == "undefined")	this.sortType = "desc";
			else if (this.sortType == "asc")		this.sortType = "desc";
			else						this.sortType = "asc";
			if (this.sortType == "asc") {
				d3.select(this).select('i').attr('class', 'fa fa-sort-up pull-right');
				rows.sort(function (a,b) {return a[keys[i]]>b[keys[i]]});
			} else {
				d3.select(this).select('i').attr('class', 'fa fa-sort-down pull-right');
				rows.sort(function (a,b) {return a[keys[i]]<b[keys[i]]});
			}
		}).append('i').attr('class', 'fa fa-sort pull-right');
	});
	chart.dispatch.on("renderUpdate.wdTableChart", function() {
		var update = chart.root().select('tbody').selectAll('tr').data(chart.data());
		keys = Object.keys(chart.data()[0]);
		update.exit().remove();
		rows = update.enter().append('tr');
		rows.selectAll('td').data(function (d, i) {
			var j=0, ret=[];
			for (var k in d) {
				if(!d.hasOwnProperty(k) || k=="actions") continue;
				ret.push({ id: ++j, rowid: i, name: k, value: d[k] })
			}
			return ret;
		}).enter().append('td').text(function (d) {return d.value });
		rows.selectAll('td.actions').data(function (d, i) {
			return d["actions"];
		}).enter().append('td').attr('class', 'actions pull-right')
		    .append('a').attr('href', function(d){return d.url})
		    .append('i').attr('class', function(d){return d.icon});
	});
	return chart;
}
function watchedTable(id, data) {
	var chart = wdTableChart().data(data);
	d3.select("#"+id).call(chart);
	return chart;
}
