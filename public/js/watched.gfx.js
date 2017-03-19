function wdGfxLegend() {
	var	chart	= wdColoredComponant();
	var	bar, gfx;
	chart.dispatch.register("enable", "select");
	chart.setValue	= function(d,v) {
		chart.root().select('#value_'+d).html(v)
		return chart;
	}
	chart.gfx	= function(_) {
		if (!arguments.length) return gfx; gfx = _;
		return chart;
	};
	chart.cols	= function() {
		var ret = [];
		if (chart.inited() && chart.ready())
			chart.data().forEach(function(d) {
			if (chart.root().select('#enable_'+d).classed('activated')) {
				switch(chart.root().select('#select_'+d).node().value) {
					case "Min": ret.push("min_"+d);break;
					case "Avg": ret.push("avg_"+d);break;
					case "Max": ret.push("max_"+d);break;
				}
			}
		})
		else if (chart.ready())
			chart.data().forEach(function(d) {
				ret.push("avg_"+d);
			})
		return ret;
	};
	chart.colColor	= function(c) {
		return chart.color()(c.substring(4))
	};
	chart.dispatch.on("dataUpdate.wdGfxLegend", function() { 
		chart.color().domain(chart.data());
	});
	chart.dispatch.on("init.wdGfxLegend", function() { 
		bar = chart.root().append('div').attr('class', 'btn-toolbar').attr('role','toolbar');
	});
	chart.dispatch.on("renderUpdate.wdGfxLegend", function() {
		var d = bar.selectAll('div.btn-group').data(chart.data()),
		    g = d.enter().append('div').attr('class', 'btn-group').attr('role','group').attr('data-toggle','buttons');
		var l = g.append('label').attr('class', 'btn btn-default item active activated')
			.attr('id', function (d) { return 'enable_'+d})
			.on('click', function (d) {
				var x=d3.select(this);
				x.classed('activated',!x.classed('activated'))
				chart.dispatch.call("enable",this,d,x.classed('activated'));
			});
		l.append('input').attr('type', 'checkbox').attr('checked','true')
		l.append('i').attr('class', 'fa fa-circle').attr('style',function (d) { return 'color:'+chart.color()(d)})
		var s = g.append('select').attr('class', 'item')
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.attr('id', function (d) { return 'select_'+d})
			.on('change', function(d,i){chart.dispatch.call("select",this,chart.data()[i],chart.root().select('#select_'+chart.data()[i]).node().value)})
		s.append('option').text('Min');
		s.append('option').text('Avg').attr('selected','true');
		s.append('option').text('Max');
		g.append('div').attr('class', 'item').append('b')
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.html(function(d){return d})
		g.append('div').attr('class', 'item').html("0.00")
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.attr('id', function (d) { return 'value_'+d})
	});
	return chart;
}

function wdGfxTimeLine() {
	var	chart	= wdAxedComponant(wdMinSizedComponant(null, 500,60)),
		legend, svg,
		brush	= d3.brushX(),
		line	= d3.line().curve(d3.curveBasis)
				.x(function(d) { return chart.xAxis(d.timestamp); })
				.y(function(d) { return chart.yAxis(d.value); }),
		lines	= [];

	chart.dispatch.register("brushed");
	chart.legend	= function(_) {if (!arguments.length) return legend;legend = _;return chart};
	chart.brushMove	= function(r) {
		svg.select('.brush').call(brush.move, r);
	}
	chart.xAxisLine	= function(g) {
		g.call(d3.axisBottom(chart.xAxis).tickFormat(wdDateFormat));
		g.select(".domain").remove();
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1.5px");
	}
	chart.dispatch.on("dataUpdate.wdAxedComponant", function() { 
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
			var vals = legend.cols().map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
		lines = legend.cols().map(function(i) {
			return {
				id: i,
				values: chart.data().map(function(d) {
					return {timestamp: d.timestamp, value:+d[i]};
				})
			};
		});

	});
	chart.dispatch.on("heightUpdate.wdGfxTimeLine", function() {
		brush.extent([[0,0],[chart.width(),chart.height()]])
		if (chart.inited())
			chart.root().select(".x.axis").attr("transform", "translate(0," + chart.height() + ")");
	});
	chart.dispatch.on("init.wdGfxTimeLine", function() {
		var bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width-30);chart.height(bound.height-20);
		svg	= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height()+20);
		svg.append("g").attr("class", "x axis").attr("transform", "translate(0," + chart.height() + ")").call(chart.xAxisLine);
		brush.on("brush end", function() {
			if (d3.event.sourceEvent && d3.event.sourceEvent.type === "zoom") return;
			//if (d3.event.sourceEvent && d3.event.sourceEvent.type === "mouseup") return;
			var s = d3.event.selection || chart.xAxis.range();
			chart.dispatch.call("brushed", this, s, s.map(chart.xAxis.invert,chart.xAxis))
		})
	})
	chart.dispatch.on("renderUpdate.wdGfxTimeLine", function() {
		chart.root().selectAll(".lines").remove();
		var	update	= svg.selectAll(".lines").data(lines, function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines");
		update.exit().remove();
		eLines.append("path").attr("class", "line")
			.attr("d", function(d) { return line(d.values); })
			.style("stroke", function(d) { return legend.colColor(d.id); });
		update.transition().select(".x.axis").duration(150).call(chart.xAxisLine);
		
		svg.append("g").attr("class", "brush").call(brush)
			.call(brush.move, chart.xAxis.range());
	})
	return chart;
}
function wdGfxChart() {
	var	chart	= wdAxesComponant(wdAxedComponant(wdMinSizedComponant(null, 500,350))),
		legend, svg, timeline, domain,
		margin	= {top: 10, right: 10, bottom: 20, left: 30},
		w	= chart.width()-(margin.left+margin.right+30),
		h	= chart.height()-margin.bottom-margin.top,
		zoom	= d3.zoom().scaleExtent([1, 100]),
		line	= d3.line().curve(d3.curveBasis)
				.x(function(d) { return chart.xAxis(d.timestamp); })
				.y(function(d) { return chart.yAxis(d.value); }),
		lines	= [];

	chart.zoomed	= function () {
		if (d3.event.sourceEvent && d3.event.sourceEvent.type === "brush") return;
		var t = d3.event.transform;
		timeline.brushMove(chart.xAxis.range().map(t.invertX, t))
		domain = t.rescaleX(timeline.xAxis).domain()
		chart.xAxis.domain(domain)
		chart.lineChanged();
		chart.dispatch.call("renderUpdate")
	}
	chart.lineChanged=function() {
		var subData= [];
		chart.data().forEach(function(d) {
			if(d.timestamp >= domain[0] && d.timestamp <= domain[1])
				subData.push(d)
		})
		chart.yAxis.domain([0, d3.max(subData, function(d) {
			var vals = legend.cols().map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
		lines = legend.cols().map(function(i) {
			return {
				id: i,
				values: subData.map(function(d) {
					return {timestamp: d.timestamp, value:+d[i]};
				})
			};
		});
		return chart;
	}
	chart.colChanged= function (d,v) {
		chart.lineChanged();
		chart.dispatch.call("renderUpdate")
		return chart;
	}
	chart.brushChanged= function (s, r) {
		domain = r;
		chart.xAxis.domain(domain);
		svg.select(".zoom").call(zoom.transform, 
			d3.zoomIdentity.scale(w / (s[1] - s[0])).translate(-s[0], 0));
		chart.lineChanged();
		chart.dispatch.call("renderUpdate")
		return chart;
	}
	chart.legend	= function(_) {
		if (!arguments.length) return legend; legend = _;
		legend.dispatch.on("enable", chart.colChanged);
		legend.dispatch.on("select", chart.colChanged);
		return chart;
	};
	chart.timeline	= function(_) {
		if (!arguments.length) return timeline; timeline = _;
		timeline.dispatch.on("brushed", chart.brushChanged);
		return chart;
	};
	chart.yAxisLine	= function(g) {
		g.call(d3.axisRight(chart.yAxis).tickSize(w));
		g.select(".domain").remove();
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1px");
		g.selectAll(".tick:not(:first-of-type) line").attr("stroke-dasharray", "5,5");
		g.selectAll(".tick text").attr("x", -20);
	};
	chart.dispatch.on("widthUpdate.wdAxedComponant", function() {
		w = chart.width()-(margin.left+margin.right+30)
		chart.xAxis.range([0, w ]);
	});
	chart.dispatch.on("heightUpdate.wdAxedComponant", function() {
		h = chart.height()-margin.bottom-margin.top;
		chart.yAxis.range([h, 0]);
		zoom.translateExtent([[0, 0], [w, h]]).extent([[0, 0], [w, h]])
	});
	chart.dispatch.on("dataUpdate.wdAxedComponant", function() {
		domain = d3.extent(chart.data(), function(d) { return d.timestamp; });
		chart.xAxis.domain(domain);
		chart.lineChanged();
	});
	chart.dispatch.on("heightUpdate.wdAxesComponant", function() { 
		if (chart.inited())
			chart.root().select(".x.axis").attr("transform", "translate("+margin.left+"," +h+ ")");
	});
	chart.dispatch.on("init.wdAxesComponant", function() {
		var bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width);chart.height(bound.height);
		svg	= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height());

		svg.append("g").attr("class", "x axis").attr("transform", "translate("+margin.left+"," + (chart.height()-margin.bottom) + ")").call(chart.xAxisLine);
		svg.append("g").attr("class", "y axis").attr("transform", "translate("+margin.left+"," + margin.top + ")").call(chart.yAxisLine);

		svg.append("defs").attr("transform", "translate("+margin.left+"," + margin.top + ")").append("clipPath").attr("id", "clip").append("rect").attr("width", w).attr("height", chart.height());

		zoom.on('zoom', chart.zoomed);
		svg.append("rect").attr("class", "zoom")
			.attr("width", w).attr("height", h)
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")")
			.call(zoom);
	})
	chart.dispatch.on("renderUpdate.wdAxesComponant", function() {
		svg.selectAll(".lines").remove();
		var	update	= svg.selectAll(".lines").data(lines, function(d) { return d.id });
		/*update.exit().remove();
		update.selectAll('path').attr("d", function(d) { return line(d.values); })*/
		var	eLines	= update.enter().append("g").attr("class", "lines").attr("transform", "translate("+margin.left+", " + (-margin.bottom) + ")");
		eLines.append("path").attr("class", "line")
			.attr("d", function(d) { return line(d.values); })
			.style("clip-path","url(#clip)")
			.style("stroke", function(d) { return legend.colColor(d.id); });
		update	= svg.transition();
		update.select(".x.axis").duration(350).call(chart.xAxisLine);
		update.select(".y.axis").duration(350).call(chart.yAxisLine);
	})
	return chart;
}
