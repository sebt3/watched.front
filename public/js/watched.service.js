/////////////////////////////////////////////////////////////////////////////////////////////
// watchedService
function wdServiceAreas(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdFilteredComponant( wdAxedComponant(null, 500, 300))),
		stack		= d3.stack(),
		area		= d3.area()
					.x(function(d, i) { return chart.xAxis(d.data.timestamp); })
					.y0(function(d) { return chart.yAxis(d[0]); })
					.y1(function(d) { return chart.yAxis(d[1]); });

	chart.dispatch.on("renderUpdate.wdServiceAreas", function() { 
		chart.root().selectAll(".lines").remove();
		var	update	= chart.root().selectAll(".lines").data(stack(chart.data()), function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines");
		update.exit().remove();
		eLines.append("path").attr("class", "area")
			.style("fill", function(d) { return chart.color()(d.key); })
			.attr("d", area);
	});
	chart.dispatch.on("dataUpdate.wdAxedComponant", function() { });
	chart.dispatch.on("dataUpdate.wdServiceAreas", function() { 
		stack.keys(chart.keys());
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) { return d.failed+d.missing+d.ok; })]);
	});
	return chart;
}

function wdServiceAxes(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdAxesComponant(null,500,200);

	chart.yAxisLine		= function(g) {
		g.call(d3.axisRight(chart.yAxis).tickSize(chart.width()).ticks(chart.yAxis.domain()[1]));
		g.select(".domain").remove();
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1px");
		g.selectAll(".tick:not(:first-of-type) line").attr("stroke-dasharray", "5,5");
		g.selectAll(".tick text").attr("x", -20).attr("dy", "-4"); 
	};

	chart.dispatch.on("dataUpdate.wdAxedComponant", function() { });
	chart.dispatch.on("dataUpdate.wdServiceAxes", function() { 
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) { return d.failed+d.missing+d.ok; })]);
	});
	return chart;
}

function wdServiceLegend(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdHLegendComponant();
	return chart;
}

function wdServiceChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdPeriodComponant( wdMinSizedComponant(null,500,330))),
		margin		= {top: 30, right: 10, bottom: 20, left: 30},
		axes 		= wdServiceAxes(),
		areas		= wdServiceAreas(),
		legend 		= wdServiceLegend(),
		baseUrl		= "",
		xRev		= d3.scaleTime().domain([0, chart.width()-margin.left-margin.right]),
		oldX		= 0;

	chart.dispatch.register("mouseMove", "updateValues");
	chart.callbacks.mouseMove	= function(x,y) {
		if (Math.abs(x-oldX)<1) return;
		oldX=x;
		var v = chart.data().find(function (d) { return d.timestamp>=xRev(x) });
		chart.dispatch.call("updateValues", null, v);
	};
	chart.dispatch.on("init.wdServiceChart", function() { 
		var	bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width);
		chart.height(bound.height);
		legend.height(margin.top - 2*margin.right)
		var 	svg	= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height());
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(areas);
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(axes);
		svg.append("g").attr("transform", "translate(" + margin.right + "," + margin.right + ")").call(legend);
		svg.on("mousemove", function() {
			var 	bBox	= svg.node().getBoundingClientRect(),
				x	= d3.event.pageX-bBox.left-margin.left-window.scrollX,
				y	= d3.event.pageY-bBox.top-margin.top-window.scrollY;
			if (	x>=0 && x<=bBox.right-bBox.left-margin.left-margin.right &&
				y>=0 && y<=bBox.bottom-bBox.top-margin.top-margin.bottom) {
				chart.dispatch.call("mouseMove", null, x,y);
			}
		});
		chart.dispatch.on("mouseMove.chart", chart.callbacks.mouseMove);
		chart.dispatch.on("updateValues.legend", legend.callbacks.updateValues);
	});
	chart.dispatch.on("dataUpdate.wdServiceChart", function() { 
		xRev.range([d3.min(chart.data(),function(d){return d.timestamp}),d3.max(chart.data(),function(d){return d.timestamp})]);
		axes.data(chart.data());
		areas.data(chart.data());
		legend.data(chart.data());
		chart.dispatch.call("updateValues", null, chart.data().find(function (d) { return d.timestamp>=xRev(oldX) }));
	});
	chart.dispatch.on("heightUpdate.wdServiceChart", function() { 
		 axes.height(chart.height() - margin.top - margin.bottom);
		areas.height(chart.height() - margin.top - margin.bottom);
	});
	chart.dispatch.on("widthUpdate.wdServiceChart", function() { 
		xRev.domain([0, chart.width()-margin.left-margin.right]);
		 axes.width(chart.width() - margin.left - margin.right);
		areas.width(chart.width() - margin.left - margin.right);
		legend.width(chart.width() - 2*margin.right);
	});
	chart.dispatch.on("colorUpdate.wdServiceChart", function() { 
		areas.color(chart.color());
		legend.color(chart.color());
	});
	chart.color(d3.scaleOrdinal(['#dd4b39','#ff851b','#b3ffb3']));
	chart.updateSizeFromMin();

	return chart;
}

function watchedService(id, baseUrl) {
	var chart = wdServiceChart().baseUrl(baseUrl);
	d3.select("#"+id).call(chart);
	return chart;
}
