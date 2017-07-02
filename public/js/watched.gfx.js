(function(global, factory) {
	if (typeof global.d3 !== 'object' || typeof global.d3.version !== 'string')
		throw new Error('watched requires d3v4');
	var v = global.d3.version.split('.');
	if (v[0] != '4')
		throw new Error('watched requires d3v4');
	if (typeof global.bs !== 'object' || typeof global.bs.version !== 'string')
		throw new Error('watched require d3-Bootstrap');
	if (typeof global.wd !== 'object')
		throw new Error('watched donut require watched componant');
	
	factory(global.wd, global);
})(this, (function(wd, global) {
wd.chart.gfxLegend = function() {
	var	chart	= wd.componant.colored();
	var	bar, prop = '';
	chart.prop = function(_) {if (!arguments.length) return prop;prop=_;return chart;}
	chart.dispatch.register("area", "enable", "select");
	chart.setValue	= function(d,v) {
		chart.root().select('#value_'+d).html(wd.format.number(v))
		return chart;
	}
	chart.setValues	= function(v) {
		if (typeof v == "undefined") return chart;
		chart.data().forEach(function(d) {
			if(! chart.root().select('#enable_'+d).classed('activated')) return;
			switch(chart.root().select('#select_'+d).node().value) {
				case "Min": chart.setValue(d, v["min_"+d]);break;
				case "Avg": chart.setValue(d, v["avg_"+d]);break;
				case "Max": chart.setValue(d, v["max_"+d]);break;
			}
		})
		return chart;
	}
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
		else if (prop!='' && chart.ready())
			ret.push("avg_"+prop);
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
		bar   = chart.root().append('div').attr('class', 'btn-toolbar').attr('role','toolbar');
		var l = bar.append('div').attr('class', 'btn-group').attr('role','group').attr('data-toggle','buttons').append('label').attr('class', 'btn btn-default item').on('click', function (d){
				var x=d3.select(this);
				if (d3.event) d3.event.preventDefault();
				x.classed('activated',!x.classed('activated'))
				chart.dispatch.call("area",this,d,x.classed('activated'));
		});
		l.append('input').attr('type', 'checkbox');
		l.append('i').attr('class', 'fa fa-area-chart')
	});
	chart.dispatch.on("renderUpdate.wdGfxLegend", function() {
		var d = bar.selectAll('div.btn-group.legend').data(chart.data()),
		    g = d.enter().append('div').attr('class', 'btn-group legend').attr('role','group').attr('data-toggle','buttons');
		var l = g.append('label').attr('class', function(d){
				var ret = 'btn btn-default item', a = 'active activated';
				if (prop==''|| d == prop) return ret+' '+a;
				return ret;
			}).attr('id', function (d) { return 'enable_'+d})
			.on('click', function (d) {
				var x=d3.select(this);
				if (d3.event) d3.event.preventDefault();
				x.classed('activated',!x.classed('activated'))
				chart.dispatch.call("enable",this,d,x.classed('activated'));
			});
		l.append('input').attr('type', 'checkbox').attr('checked',function(d){if (prop==''||d == prop) return 'true';return 'false'})
		l.append('i').attr('class', 'fa fa-circle').attr('style',function (d) { return 'color:'+chart.color()(d)})
		var s = g.append('select').attr('class', 'item')
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.attr('id', function (d) { return 'select_'+d})
			.on('change', function(d,i){chart.dispatch.call("select",this,chart.data()[i],chart.root().select('#select_'+chart.data()[i]).node().value)})
		s.append('option').text(wd.lang.tr('Min'));
		s.append('option').text(wd.lang.tr('Avg')).attr('selected','true');
		s.append('option').text(wd.lang.tr('Max'));
		g.append('div').attr('class', 'item').append('b')
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.html(function(d){return d})
		g.append('div').attr('class', 'item value').html("0.00")
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.attr('id', function (d) { return 'value_'+d})
	});
	return chart;
}

wd.chart.gfxAvailLegend = function() {
	var	chart	= wd.chart.gfxLegend();
	var	bar;
	chart.setValues	= function(v) {
		if (typeof v == "undefined") return chart;
		chart.data().forEach(function(d) {
			if(! chart.root().select('#enable_'+d).classed('activated')) return;
			chart.setValue(d, v[d]);
		})
		return chart;
	}
	chart.cols	= function() {
		var ret = [];
		if (chart.inited() && chart.ready())
			chart.data().forEach(function(d) {
				if (chart.root().select('#enable_'+d).classed('activated')) ret.push(d)
			})
		else if (chart.ready())
			return chart.data();
		return ret;
	};
	chart.colColor	= function(c) {
		return chart.color()(c)
	};
	chart.dispatch.on("init.wdGfxLegend", function() { 
		bar   = chart.root().append('div').attr('class', 'btn-toolbar').attr('role','toolbar');
	});
	chart.dispatch.on("renderUpdate.wdGfxLegend", function() {
		var d = bar.selectAll('div.btn-group.legend').data(chart.data()),
		    g = d.enter().append('div').attr('class', 'btn-group legend').attr('role','group').attr('data-toggle','buttons');
		var l = g.append('label').attr('class', 'btn btn-default item active activated')
			.attr('id', function (d) { return 'enable_'+d})
			.on('click', function (d) {
				if (d3.event) d3.event.preventDefault();
				var x=d3.select(this);
				x.classed('activated',!x.classed('activated'))
				chart.dispatch.call("enable",this,d,x.classed('activated'));
			});
		l.append('input').attr('type', 'checkbox').attr('checked','true')
		l.append('i').attr('class', 'fa fa-circle').attr('style',function (d) { return 'color:'+chart.color()(d)})
		g.append('div').attr('class', 'item').append('b')
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.html(function(d){return d})
		g.append('div').attr('class', 'item value').html("0.00")
			.attr('style',function (d) { return 'color:'+chart.color()(d)})
			.attr('id', function (d) { return 'value_'+d})
	});
	chart.color(d3.scaleOrdinal(['#dd4b39','#ff851b','#b3ffb3']));
	chart.data(['failed','missing','ok']);
	return chart;
}

wd.chart.timeline = function() {
	var	chart	= wd.componant.axed(wd.componant.minSized(null, 500,60)),
		legend, svg, useArea=false,
		brush	= d3.brushX(),
		stack	= d3.stack(),
		line	= d3.line().curve(d3.curveBasis)
				.x(function(d) { return chart.xAxis(d.timestamp); })
				.y(function(d) { return chart.yAxis(d.value); }),
		area	= d3.area()
			.x(function(d, i) { return chart.xAxis(d.data.timestamp); })
			.y0(function(d) { return chart.yAxis(d[0]); })
			.y1(function(d) { return chart.yAxis(d[1]); });
		lines	= [];

	chart.dispatch.register("brushed");
	chart.areaSet= function(d,v) {
		useArea = v
		if (v)
			chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
				var vals = legend.cols().map(function (i) {return d[i]});
				return d3.sum(vals);
			})]);
		else
			chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
				var vals = legend.cols().map(function (i) {return d[i]});
				return d3.max(vals);
			})]);
		if (chart.inited() && chart.ready())
			chart.dispatch.call("renderUpdate")
	}
	chart.legend	= function(_) {
		if (!arguments.length) return legend;legend = _;
		legend.dispatch.on("area.wdGfxTimeLine", chart.areaSet);
		return chart
	};
	chart.brushMove	= function(r) {
		svg.select('.brush').call(brush.move, r);
	}
	chart.xAxisLine	= function(g) {
		g.call(d3.axisBottom(chart.xAxis).tickFormat(wd.format.dateAxe));
		g.select(".domain").remove();
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1.5px");
	}
	chart.dispatch.on("dataUpdate.wd.componant.axed", function() { 
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
		stack.keys(legend.cols());
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
		svg.append("g").attr("class", "brush").call(brush)
			.call(brush.move, chart.xAxis.range());
	})
	chart.dispatch.on("renderUpdate.wdGfxTimeLine", function() {
		chart.root().selectAll(".lines").remove();
		if (useArea) {
			var	update	= svg.selectAll(".lines").data(stack(chart.data()), function(d) { return d.timestamp }),
				eLines	= update.enter().append("g").attr("class", "lines");
			update.exit().remove();
			eLines.append("path").attr("class", "area")
				.style("fill", function(d) { return legend.colColor(d.key); })
				.attr("d", area);
		} else {
			var	update	= svg.selectAll(".lines").data(lines, function(d) { return d.timestamp }),
				eLines	= update.enter().append("g").attr("class", "lines");
			update.exit().remove();
			eLines.append("path").attr("class", "line")
				.attr("d", function(d) { return line(d.values); })
				.style("stroke", function(d) { return legend.colColor(d.id); });
		}
		svg.select("g.brush").call(chart.onTop);
		svg.transition().select(".x.axis").duration(150).call(chart.xAxisLine);
	})
	return chart;
}
wd.chart.gfx = function() {
	var	chart	= wd.componant.axes(wd.componant.axed(wd.componant.minSized(null, 500,350))),
		legend, svg, timeline, domain, full_domain, oldX = 0, useArea=false,
		margin	= {top: 10, right: 10, bottom: 20, left: 30},
		xRev	= d3.scaleTime().domain([0, chart.width()-margin.left-margin.right]),
		w	= chart.width()-(margin.left+margin.right+30),
		h	= chart.height()-margin.bottom-margin.top,
		zoom	= d3.zoom().scaleExtent([1, 100]),
		stack	= d3.stack(),
		line	= d3.line().curve(d3.curveBasis)
				.x(function(d) { return chart.xAxis(d.timestamp); })
				.y(function(d) { return chart.yAxis(d.value); }),
		area	= d3.area()
			.x(function(d, i) { return chart.xAxis(d.data.timestamp); })
			.y0(function(d) { return chart.yAxis(d[0]); })
			.y1(function(d) { return chart.yAxis(d[1]); });
		lines	= [];

	chart.dispatch.register("updateValues");
	chart.mouseMove	= function(x,y) {
		if (Math.abs(x-oldX)<1) return;
		oldX=x;
		var v = chart.data().find(function (d) { return d.timestamp>=xRev(x) });
		chart.dispatch.call("updateValues", null, v);
	};
	chart.zoomed	= function () {
		if (d3.event.sourceEvent && d3.event.sourceEvent.type === "brush") return;
		var t = d3.event.transform;
		if (typeof timeline !== 'undefined') {
			timeline.brushMove(chart.xAxis.range().map(t.invertX, t))
			domain = t.rescaleX(timeline.xAxis).domain()
		} else {
			chart.xAxis.domain(full_domain)
			domain = t.rescaleX(chart.xAxis).domain()
		}
		xRev.range(domain)
		chart.xAxis.domain(domain)
		chart.lineChanged();
		chart.noDots();
		chart.dispatch.call("renderUpdate")
	}
	chart.noDots	= function() {
		svg.selectAll(".dots").selectAll('circle').transition().duration(200).attr('r', 0);
		svg.selectAll(".dots").transition().duration(200).on("end", function(){d3.select(this).remove()});
	}
	chart.lineChanged=function() {
		var subData= [];
		stack.keys(legend.cols())
		chart.data().forEach(function(d) {
			if(d.timestamp >= domain[0] && d.timestamp <= domain[1])
				subData.push(d)
		})
		if (useArea)
			chart.yAxis.domain([0, d3.max(subData, function(d) {
				var vals = legend.cols().map(function (i) {return d[i]});
				return d3.sum(vals);
			})]);
		else
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
	chart.areaSet= function(d,v) {
		useArea = v
		if (chart.inited() && chart.ready()) {
			chart.lineChanged();
			chart.noDots();
			chart.dispatch.call("renderUpdate")
		}
	}
	chart.colChanged= function (d,v) {
		chart.lineChanged();
		chart.noDots();
		chart.dispatch.call("renderUpdate")
		return chart;
	}
	chart.brushChanged= function (s, r) {
		domain = r;
		xRev.range(domain);
		chart.xAxis.domain(domain);
		svg.select(".zoom").call(zoom.transform, 
			d3.zoomIdentity.scale(w / (s[1] - s[0])).translate(-s[0], 0));
		chart.noDots();
		chart.lineChanged();
		chart.dispatch.call("renderUpdate")
		return chart;
	}
	chart.legend	= function(_) {
		if (!arguments.length) return legend; legend = _;
		legend.dispatch.on("area.wdGfxChart", chart.areaSet);
		legend.dispatch.on("enable", chart.colChanged);
		legend.dispatch.on("select", chart.colChanged);
		chart.dispatch.on("updateValues.legend", legend.setValues);
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
	chart.dispatch.on("widthUpdate.wd.componant.axed", function() {
		w = chart.width()-(margin.left+margin.right+30)
		xRev.domain([0, w]);
		chart.xAxis.range([0, w ]);
	});
	chart.dispatch.on("heightUpdate.wd.componant.axed", function() {
		h = chart.height()-margin.bottom-margin.top;
		chart.yAxis.range([h, 0]);
		zoom.translateExtent([[0, 0], [w, h]]).extent([[0, 0], [w, h]])
	});
	chart.dispatch.on("dataUpdate.wd.componant.axed", function() {
		domain = d3.extent(chart.data(), function(d) { return d.timestamp; });
		full_domain = domain;
		xRev.range(domain);
		chart.xAxis.domain(domain);
		chart.lineChanged();
	});
	chart.dispatch.on("heightUpdate.wd.componant.axes", function() { 
		if (chart.inited())
			chart.root().select(".x.axis").attr("transform", "translate("+margin.left+"," +h+ ")");
	});
	chart.dispatch.on("init.wd.componant.axes", function() {
		var bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width);
		chart.height(bound.height);chart.dispatch.call("heightUpdate")
		svg	= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height());
		svg.on("mousemove", function() {
			var 	bBox	= svg.node().getBoundingClientRect(),
				x	= d3.event.pageX-bBox.left-margin.left-window.scrollX,
				y	= d3.event.pageY-bBox.top-margin.top-window.scrollY;
			if (	x>=0 && x<=bBox.right-bBox.left-margin.left-margin.right &&
				y>=0 && y<=bBox.bottom-bBox.top-margin.top-margin.bottom) {
				chart.mouseMove(x,y);
			}
		});

		svg.append("g").attr("class", "x axis").attr("transform", "translate("+margin.left+"," + (chart.height()-margin.bottom) + ")").call(chart.xAxisLine);
		svg.append("g").attr("class", "y axis").attr("transform", "translate("+margin.left+"," + margin.top + ")").call(chart.yAxisLine);

		svg.append("defs").attr("transform", "translate("+margin.left+"," + margin.top + ")").append("clipPath").attr("id", "clip").append("rect").attr("width", w).attr("height", h);

		zoom.on('zoom', chart.zoomed);
		svg.append("rect").attr("class", "zoom")
			.attr("width", chart.width()).attr("height", chart.height())
			.call(zoom);
	})
	chart.dispatch.on("updateValues.wdGfxChart", function(v) {
		chart.noDots();
		if (typeof v == "undefined") return;
		var dots = legend.cols().map(function(d,i){
			return {
				id:	d.substr(4),
				timestamp:v.timestamp,
				value:	v[d],
				color:	legend.colColor(d),
				x:	chart.xAxis(v.timestamp),
				y:	chart.yAxis(useArea?stack([v])[i][0][1]:v[d])
			};
		}), update = svg.selectAll(".dot2s").data(dots);
		update.enter().append('g').attr('class','dots')
			.attr("transform", "translate("+margin.left+", " + (margin.top) + ")")
			.append('circle').attr('cx', function(d){return d.x})
				.attr('cy', function(d){return d.y})
				.attr('stroke', function(d){return d.color})
				.transition().duration(200).attr('r', 5)

	});
	chart.drawArea	= function() {
		var	update	= svg.selectAll(".lines").data(stack(chart.data()), function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines").attr("transform", "translate("+margin.left+", " + (margin.top) + ")");
		update.exit().remove();
		eLines.append("path").attr("class", "area")
			.style("clip-path","url(#clip)")
			.style("fill", function(d) { return legend.colColor(d.key); })
			.attr("d", area);
	}
	chart.drawLine	= function() {
		var	update	= svg.selectAll(".lines").data(lines, function(d) { return d.id });
		/*update.exit().remove();
		update.selectAll('path').attr("d", function(d) { return line(d.values); })*/
		var	eLines	= update.enter().append("g").attr("class", "lines").attr("transform", "translate("+margin.left+", " + (margin.top) + ")");
		eLines.append("path").attr("class", "line")
			.attr("d", function(d) { return line(d.values); })
			.style("clip-path","url(#clip)")
			.style("stroke", function(d) { return legend.colColor(d.id); });
	}
	chart.dispatch.on("renderUpdate.wd.componant.axes", function() {
		svg.selectAll(".lines").remove();
		if (useArea)
			chart.drawArea();
		else
			chart.drawLine();
		svg.select("rect.zoom").call(chart.onTop);
		var update	= svg.transition();
		update.select(".x.axis").duration(350).call(chart.xAxisLine);
		update.select(".y.axis").duration(350).call(chart.yAxisLine);
	})
	return chart;
}
}));
