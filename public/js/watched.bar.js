/////////////////////////////////////////////////////////////////////////////////////////////
// watchedBarChart
function wdBarChartBars(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdFilteredComponant( wdAxedComponant(null, 200, 200))),
		stack		= d3.stack();
	chart.xAxis		= d3.scaleBand().padding(0.2);
	chart.colorFunction	= function() { return chart.color(); }
	chart.dispatch.register("click");
	chart.dispatch.on("renderUpdate.wdBarChartBars", function() {
		chart.root().selectAll(".bars").remove();
		var	update	= chart.root().selectAll(".bars").data(stack(chart.data()), function(d) { return d.type }),
			eBars	= update.enter().append("g").attr("class", "bars");
		update.exit().remove();
		eBars.append("g")
			.selectAll("rect")
			.data(function(d) {d.map(function(i){i.key=d.key});return d; })
				.enter().append("rect")
				.attr("fill", function(d,i) { return chart.colorFunction(d,i)(d.key); })
				.attr("x", function(d) { return chart.xAxis(d.data.type); })
				.attr("y", function(d) { return chart.yAxis(d[1]); })
				.attr("height", function(d) { return chart.yAxis(d[0]) - chart.yAxis(d[1]); })
				.attr("width", chart.xAxis.bandwidth())
				.on("click",function (d,i){chart.dispatch.call("click", this,d,i);});
	});
	chart.dispatch.on("widthUpdate.wdAxedComponant", function() { });
	chart.dispatch.on("widthUpdate.wdBarChartBars", function() {
		chart.xAxis.rangeRound([0, chart.width()]);
	});
	chart.dispatch.on("dataUpdate.wdAxedComponant", function() { });
	chart.dispatch.on("dataUpdate.wdBarChartBars", function() {
		var keys = [];
		Object.keys(chart.data()[0]).map(function(k, i) {
			if(typeof chart.data()[0][k] == "number")
				keys.push(k);
		});
		stack.keys(keys);
		chart.color().domain(keys);
		chart.xAxis.domain(chart.data().map(function(d) { return d.type; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) { 
			var sum=0;
			Object.keys(d).map(function(k, i) {
				if(typeof d[k] == "number")
					sum+=d[k];
			});
			return sum;
		})]);
	});
	return chart;
}

function wdBarAxes(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdAxesComponant(null,500,200);
	chart.xAxis		= d3.scaleBand().rangeRound([0, chart.width()]).paddingInner(0.05).align(0.1);
	chart.xAxisLine		= function(g) {
		g.call(d3.axisBottom(chart.xAxis));
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1.5px");
	}
	chart.dispatch.on("widthUpdate.wdAxedComponant", function() { });
	chart.dispatch.on("widthUpdate.wdBarAxes", function() {
		chart.xAxis.rangeRound([0, chart.width()]);
	});
	chart.dispatch.on("dataUpdate.wdAxedComponant", function() { });
	chart.dispatch.on("dataUpdate.wdBarAxes", function() { 
		chart.xAxis.domain(chart.data().map(function(d) { return d.type; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) { 
			var sum=0;
			Object.keys(d).map(function(k, i) {
				if(typeof d[k] == "number")
					sum+=d[k];
			});
			return sum;
		})]);
	});
	return chart;
}

function wdBarChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdPeriodComponant( wdMinSizedComponant(null,200,200))),
		margin		= {top: 10, right: 10, bottom: 20, left: 30},
		axes 		= wdBarAxes(),
		bars		= wdBarChartBars();

	chart.dispatch.on("init.wdBarChart", function() { 
		var	bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width);
		chart.height(bound.height);
		var 	svg	= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height());
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(bars);
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(axes);
	});
	chart.dispatch.on("heightUpdate.wdBarChart", function() { 
		axes.height(chart.height() - margin.top - margin.bottom);
		bars.height(chart.height() - margin.top - margin.bottom);
	});
	chart.dispatch.on("widthUpdate.wdBarChart", function() { 
		axes.width(chart.width() - margin.left - margin.right);
		bars.width(chart.width() - margin.left - margin.right);
	});
	chart.dispatch.on("dataUpdate.wdBarChart", function() { 
		axes.data(chart.data());
		bars.data(chart.data());
	});
	chart.dispatch.on("colorUpdate.wdBarChart", function() { 
		bars.color(chart.color());
	});
	chart.bars	= function() {return bars }
	chart.updateSizeFromMin();
	return chart;
}

function watchedBarChart(id, data) {
	var chart = wdBarChart().data(data);
	d3.select("#"+id).call(chart);
	return chart;
}

function watchedMemSwap(id, data) {
	var chart = wdBarChart();
	var cmem  = d3.scaleOrdinal(['#00a65a','#cccccc']).domain(['used','free'])
	var cswap = d3.scaleOrdinal(['#dd4b39','#cccccc']).domain(['used','free'])
	chart.bars().colorFunction	= function(d) { 
		if (d.data.type=="memory")
			return cmem;
		return cswap;
	}
	chart.bars().dispatch.on("click.watchedMemSwap", function(d) {window.location.href =d.data.url})
	chart.data(data);
	d3.select("#"+id).call(chart);
	return chart;
}
