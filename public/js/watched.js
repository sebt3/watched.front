function log(text) {
  if (console && console.log) console.log(text);
  return text;
}

if (!Date.now) {
    Date.now = function() { return new Date().getTime(); }
}

/////////////////////////////////////////////////////////////////////////////////////////////
// watched Common Component
function wdDateFormat(date) {
	var	locale = d3.timeFormatLocale({
			"dateTime": "%A, le %e %B %Y, %X",
			"date": "%Y-%m-%d",
			"time": "%H:%M",
			"periods": ["AM", "PM"],
			"days": ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
			"shortDays": ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
			"months": ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"],
			"shortMonths": ["janv.", "févr.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc."]
		}),formatMillisecond	= locale.format(".%L"),
		formatSecond		= locale.format(":%S"),
		formatMinute		= locale.format("%X"),
		formatHour		= locale.format("%X"),
		formatDay		= locale.format("%x"),
		formatWeek		= locale.format("%x"),
		formatMonth		= locale.format("%x"),
		formatYear		= locale.format("%Y");
	return (d3.timeSecond(date) < date ? formatMillisecond
		: d3.timeMinute(date) < date ? formatSecond
		: d3.timeHour(date) < date ? formatMinute
		: d3.timeDay(date) < date ? formatHour
		: d3.timeMonth(date) < date ? (d3.timeWeek(date) < date ? formatDay : formatWeek)
		: d3.timeYear(date) < date ? formatMonth
		: formatYear)(date);
}

function wdAxes() {
	var	data		= [],
		width		= 500,
		height		= 400,
		callbacks	= {},
		root, marq,
		x		= d3.scaleTime().range([0, width]),
		y		= d3.scaleLinear().range([height, 0]),
		filter		= function(e){return e!="timestamp";},
		xAxis		= function(g) {
			g.call(d3.axisBottom(x).tickFormat(wdDateFormat));
			g.select(".domain").remove();
			g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1.5px");},
		yAxis		= function(g) {
			g.call(d3.axisRight(y).tickSize(width));
			g.select(".domain").remove();
			g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1px");
			g.selectAll(".tick:not(:first-of-type) line").attr("stroke-dasharray", "5,5");
			g.selectAll(".tick text").attr("x", -20);};

	function chart(selection) {
		selection.each(function() {
			root	= d3.select(this);
			marq	= root.append("line").attr("x1", 0).attr("y1", 0)
					.attr("x2", 0).attr("y2", height)
					.attr("shape-rendering", "crispEdges")
					.style("stroke-width", 1).style("stroke", "black")
					.style("fill", "none");
			root.append("g").attr("class", "x axis").attr("transform", 
						"translate(0," + height + ")").call(xAxis);
			root.append("g").attr("class", "y axis").call(yAxis);
		});
		return chart;
	}
	callbacks["mouseMove"]	= function(x,y) {
		if (typeof marq === 'undefined') return;
		marq.attr("x1", x).attr("x2", x)
	};
	chart.callbacks	= callbacks;
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = _;
		y.range([height, 0]);
		if (typeof root !== 'undefined')
			root.select(".x.axis").attr("transform", "translate(0," + height + ")");
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = _;
		x.range([0, width]);
		if (typeof root === 'undefined') return chart;
		var	update	= root.transition();
		update.select(".x.axis").duration(150).call(xAxis);
		update.select(".y.axis").duration(150).call(yAxis);
		return chart;
	};
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		x.domain(d3.extent(data, function(d) { return d.timestamp; }));
		y.domain([0, d3.max(data, function(d) {
			var keys = Object.keys(d).filter(filter),
			    vals = keys.map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
		return chart;
	};
	chart.filter	= function(_) {
		if (!arguments.length) return filter;
		filter = _;
		return chart;
	};

	return chart;
}

function wdHLegend() {
	var	data		= [],
		color		= d3.scaleOrdinal(d3.schemeCategory10),
		width		= 500,
		height		= 40,
		callbacks	= {},
		filter		= function(e){return e!="timestamp";},
		keys		= [],
		root, renderUpdate;

	function chart(selection) {
		selection.each(function() {
			var	root	= d3.select(this);
		});
		return chart;
	}
	renderUpdate		= function() {
		var	update	= root.transition();
	};
	callbacks["mouseMove"]	= function(x,y) {
	}
	chart.callbacks	= callbacks;
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		keys = Object.keys(data[0]).filter(filter);
		if (typeof root !== 'undefined')
			renderUpdate();
		return chart;
	};
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = _;
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = _;
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		return chart;
	};
	chart.filter	= function(_) {
		if (!arguments.length) return filter;
		filter = _;
		return chart;
	};

	return chart;
}

/////////////////////////////////////////////////////////////////////////////////////////////
// watchedEvent

function wdEventLines() {
	var	color		= d3.scaleOrdinal(d3.schemeCategory10),
		callbacks	= {},
		width		= 500,
		height		= 400,
		x		= d3.scaleTime().range([0, width]),
		y		= d3.scaleLinear().range([height, 0]),
		line		= d3.line().curve(d3.curveBasis)
					.x(function(d) { return x(d.timestamp); })
					.y(function(d) { return y(d.value); }),
		filter		= function(e){return e!="timestamp";},
		data		= [],
		keys		= [],
		lines		= [],
		root, renderUpdate;

	function chart(selection) {
		selection.each(function() {
			root		= d3.select(this);
		});
		return chart;
	}
	renderUpdate	= function() {
		root.selectAll(".lines").remove();
		var	update	= root.selectAll(".lines").data(lines, function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines");
		update.exit().remove();
		eLines.append("path").attr("class", "line")
			.attr("d", function(d) { return line(d.values); })
			.style("stroke", function(d) { return color(d.id); });
	};
	callbacks["mouseMove"]	= function(x,y) {
	};
	chart.callbacks	= callbacks;
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		keys = Object.keys(data[0]).filter(filter);
		lines = keys.map(function(i) {
			return {
				id: i,
				values: data.map(function(d) {
					return {timestamp: d.timestamp, value:+d[i]};
				})
			};
		});
		x.domain(d3.extent(data, function(d) { return d.timestamp; }));
		y.domain([0, d3.max(data, function(d) {
			var keys = Object.keys(d).filter(filter),
			    vals = keys.map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
		color.domain(keys);
		if (typeof root !== 'undefined') 
			renderUpdate();
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		return chart;
	};
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = _;
		y.range([height, 0]);
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = _;
		x.range([0, width]);
		return chart;
	};
	chart.filter	= function(_) {
		if (!arguments.length) return filter;
		filter = _;
		return chart;
	};

	return chart;
}

function wdEventChart() {
	var	margin		= {top: 50, right: 10, bottom: 20, left: 30},
		color		= d3.scaleOrdinal(d3.schemeCategory10),
		dispatch	= d3.dispatch("mouseMove"),
		axes 		= wdAxes(),
		legend 		= wdHLegend().color(color),
		lines		= wdEventLines().color(color),
		data		= [],
		baseUrl		= "",
		minWidth	= 500,
		minHeight	= 400,
		width		= minWidth, 
		height		= minHeight,
		prop		= "",
		filter		= function(e){return e!="timestamp";},
		root, bound, svg, updateData;

	function chart(selection) {
		selection.each(function() {
			root	= d3.select(this);
			bound	= root.node().getBoundingClientRect();
			width	= Math.max(bound.width, minWidth);
			height	= Math.max(bound.height, minHeight);
			axes.width(width - margin.left - margin.right).height(height - margin.top - margin.bottom);
			lines.width(width - margin.left - margin.right).height(height - margin.top - margin.bottom);
			legend.width(width - 2*margin.right).height(margin.top - 2*margin.right)
			svg	= root.append("svg").attr("width", width).attr("height", height);
			svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(lines);
			svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(axes);
			svg.append("g").attr("transform", "translate(" + margin.right + "," + margin.right + ")").call(legend);
			svg.on("mousemove", function() {
				var 	bBox	= root.node().getBoundingClientRect();
					x	= d3.event.pageX-bBox.left-margin.left,
					y	= d3.event.pageY-bBox.top-margin.top;
				if (	x>0 && x<bBox.right-bBox.left-margin.left-margin.right &&
					y>0 && y<bBox.bottom-bBox.top-margin.top-margin.bottom) {
					dispatch.call("mouseMove", null, x,y);
				}
			});
			dispatch.on("mouseMove.axis", axes.callbacks["mouseMove"]);
			dispatch.on("mouseMove.lines", lines.callbacks["mouseMove"]);
			dispatch.on("mouseMove.legend", legend.callbacks["mouseMove"]);
		});
		return chart;
	}
	updateData	= function(_) {
		if (!arguments.length) return data;
		if (typeof _!="object" || typeof _[0]=="undefined") return chart;
		data = _;
		axes.data(data);
		lines.data(data);
		legend.data(data);
		return chart;
	};
	chart.dispatch	= dispatch;
	chart.data	= updateData;
	chart.prop	= function(_) {
		if (!arguments.length) return prop;
		prop = _;
		filter = function(e){return e!="timestamp" && (e.match("avg_"+prop) || e==prop );};
		axes.filter(filter);
		lines.filter(filter);
		return chart;
	};
	chart.baseUrl	= function(_) {
		if (!arguments.length) return data;
		baseUrl = _;
		$.getJSON(baseUrl, function(results) {
			if (typeof results!="object" || typeof results[0]=="undefined") return;
			updateData(results); 
		});

		return chart;
	};
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = Math.max(_, minHeight);
		axes.height(height - margin.top - margin.bottom);
		lines.height(height - margin.top - margin.bottom);
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = Math.max(_, minWidth);
		axes.width(width - margin.left - margin.right);
		lines.width(width - margin.left - margin.right);
		legend.width(width - 2*margin.right);
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		lines.color(color);
		legend.color(color);
		return chart;
	};
	return chart;
}


function watchedEvent(id, baseUrl, prop) {
	var chart = wdEventChart()
		.prop(prop)
		.baseUrl(baseUrl);
	d3.select("#"+id).call(chart);
	return chart;
}
/////////////////////////////////////////////////////////////////////////////////////////////
// watchedService
function wdServiceAreas() {
	var	color		= d3.scaleOrdinal(d3.schemeCategory10),
		width		= 500,
		height		= 400,
		x		= d3.scaleTime().range([0, width]),
		y		= d3.scaleLinear().range([height, 0]),
		data		= [],
		keys		= [],
		stack		= d3.stack(),
		area		= d3.area()
					.x(function(d, i) { return x(d.data.timestamp); })
					.y0(function(d) { return y(d[0]); })
					.y1(function(d) { return y(d[1]); }),
		lines		= [],
		root, renderUpdate;

	function chart(selection) {
		selection.each(function() {
			root	= d3.select(this);
		});
		return chart;
	}
	renderUpdate	= function() {
		root.selectAll(".lines").remove();
		var	update	= root.selectAll(".lines").data(stack(data), function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines");
		update.exit().remove();
		eLines.append("path").attr("class", "area")
			.style("fill", function(d) { return color(d.key); })
			.attr("d", area);
	};
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		keys = Object.keys(data[0]).filter(function(e){return e!="timestamp"});
		lines = keys.map(function(i) {
			return {
				id: i,
				values: data.map(function(d) {
					return {timestamp: d.timestamp, value:+d[i]};
				})
			};
		});
		stack.keys(keys);
		x.domain(d3.extent(data, function(d) { return d.timestamp; }));
		y.domain([0, d3.max(data, function(d) { return d.failed+d.missing+d.ok; })]);
		if (typeof root !== 'undefined') renderUpdate();
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		return chart;
	};
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = _;
		y.range([height, 0]);
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = _;
		x.range([0, width]);
		return chart;
	};

	return chart;
}

function wdServiceAxes() {
	var	data		= [],
		width		= 500,
		height		= 400,
		x		= d3.scaleTime().range([0, width]),
		y		= d3.scaleLinear().range([height, 0]),
		xAxis		= function(g) {
			g.call(d3.axisBottom(x).tickFormat(wdDateFormat));
			g.select(".domain").remove();
			g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1.5px"); },
		yAxis	= function(g) {
			g.call(d3.axisRight(y).tickSize(width).ticks(y.domain()[1]));
			g.select(".domain").remove();
			g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1px");
			g.selectAll(".tick:not(:first-of-type) line").attr("stroke-dasharray", "5,5");
			g.selectAll(".tick text").attr("x", -20).attr("dy", "-4"); },
		root, renderUpdate;

	function chart(selection) {
		selection.each(function() {
			root	= d3.select(this);
			root.append("g").attr("class", "x axis").attr("transform", 
						"translate(0," + height + ")").call(xAxis);
			root.append("g").attr("class", "y axis").call(yAxis);
		});
		return chart;
	}
	renderUpdate	= function() {
		var	update	= root.transition();
		update.select(".x.axis").duration(150).call(xAxis);
		update.select(".y.axis").duration(150).call(yAxis);
	};
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = _;
		y.range([height, 0]);
		if (typeof root === 'undefined') return chart;
		root.select(".x.axis").attr("transform", "translate(0," + height + ")");
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = _;
		x.range([0, width]);
		return chart;
	};
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		x.domain(d3.extent(data, function(d) { return d.timestamp; }));
		y.domain([0, d3.max(data, function(d) { return d.failed+d.missing+d.ok; })]);
		if (typeof root !== 'undefined') renderUpdate();
		return chart;
	};

	return chart;
}

function wdServiceChart() {
	var	margin		= {top: 10, right: 10, bottom: 20, left: 30},
		color		= function (k) {
			switch(k) {
				case "missing": return '#ff851b';
				case "failed": return '#dd4b39';
				default: return '#b3ffb3';
			}
		},
		axes 		= wdServiceAxes(),
		areas		= wdServiceAreas().color(color),
		data		= [],
		baseUrl		= "",
		minWidth	= 500,
		minHeight	= 400,
		width		= minWidth, 
		height		= minHeight,
		root, bound, svg, setData, setPeriod, setHeight, setWidth;

	function chart(selection) {
		selection.each(function() {
			root	= d3.select(this),
			bound	= root.node().getBoundingClientRect();
			setWidth(bound.width);
			setHeight(bound.height);
			svg	= root.append("svg").attr("width", width).attr("height", height);
			svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(areas);
			svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(axes);
		});
		return chart;
	}
	setData		= function(_) {
		if (!arguments.length) return data;
		if (typeof _!="object" || typeof _[0]=="undefined") return chart;
		data = _;
		axes.data(data);
		areas.data(data);
		return chart;
	}
	setPeriod	= function(p) {
		var url;
		switch(p) {
			case "month": url=baseUrl+"/"+(Math.floor(Date.now())-(3600000*24*31));break;
			case "week": url=baseUrl+"/"+(Math.floor(Date.now())-(3600000*24*7));break;
			case "yesterday": url=baseUrl+"/"+((Math.floor(Date.now()/(3600000*24))-1)*(3600000*24))+"/"+(Math.floor(Date.now()/(3600000*24))*(3600000*24));break;
			case "today": url=baseUrl+"/"+(Math.floor(Date.now()/(3600000*24))*(3600000*24));break;
			case "hour": url=baseUrl+"/"+(Math.floor(Date.now()/(3600000))*(3600000));break;
			case "all":
			default:
				url=baseUrl;
		}
		$.getJSON(url, function(results) {
			if (typeof results!="object" || typeof results[0]=="undefined") return;
			setData(results); 
		});
	}
	setHeight	= function(_) {
		if (!arguments.length) return height;
		height = Math.max(_, minHeight);
		axes.height(height - margin.top - margin.bottom);
		areas.height(height - margin.top - margin.bottom);
		return chart; },
	setWidth	= function(_) {
		if (!arguments.length) return width;
		width = Math.max(_, minWidth);
		axes.width(width - margin.left - margin.right);
		areas.width(width - margin.left - margin.right);
		return chart;
	};
	chart.setPeriod = setPeriod;
	chart.height	= setHeight;
	chart.width	= setWidth;
	chart.data	= setData;
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		return chart;
	};
	chart.baseUrl	= function(_) {
		if (!arguments.length) return data;
		baseUrl = _;
		setPeriod();
		return chart;
	};
	return chart;
}

function watchedService(id, baseUrl) {
	var chart = wdServiceChart();
		chart.baseUrl(baseUrl);
		d3.select("#"+id).call(chart);
	return chart;
}

/////////////////////////////////////////////////////////////////////////////////////////////
// watchedDonut

function wdDonutChartLegend() {
	var	color		= d3.scaleOrdinal(d3.schemeCategory10),
		dispatch	= d3.dispatch("itemMouseOver", "itemMouseOut"),
		callbacks	= {},
		data		= [],
		root, renderUpdate;

	function chart(selection) {
		selection.each(function() {
			root	= d3.select(this);
			dispatch.on("itemMouseOver.legend", callbacks["itemMouseOver"]).on("itemMouseOut.legend", callbacks["itemMouseOut"]);
		});
		return chart;
	}
	renderUpdate	= function() {
		root.selectAll("li").selectAll("i").remove();
		root.selectAll("li").selectAll("span").remove();
		var	update	= root.selectAll("li").data(data, function(d) { return d ? d.label : this.id; }),
			liHtml	= update.enter().append("li")
					.merge(update).attr("id", function(d, i) { return "li-" + i });
		liHtml.append("i").attr("class", "fa fa-circle-o")
			.attr("style", function (d,i) { 
				if (typeof d.color !== 'undefined') 
					return "color:"+d.color+";";
				return "color:"+color(i)+";";
			});
		liHtml.append("span").text(function (d) {return " "+d.label;});
		liHtml.append("span").attr("class", "pull-right").text(function (d) {return d.value;});
		liHtml	.on("mouseover", function(d, i){dispatch.call("itemMouseOver", null, d, i);})
			.on("mouseout", function(d, i) {dispatch.call("itemMouseOut", null, d, i);})
		update.exit().remove();
	};
	callbacks["itemMouseOver"]	= function(d, i) {
		if (typeof root === 'undefined') return;
		var c = color(i);
		if (typeof data[i].color !== 'undefined') {c=data[i].color;}
		root.selectAll("#li-"+i)
			.style("background-color", c)
			.style("font-weight","bold")
	};
	callbacks["itemMouseOut"]	= function(d, i) {
		root.selectAll("#li-"+i)
			.style("background-color", "white")
			.style("font-weight","normal")
	};
	chart.dispatch	= dispatch;
	chart.callbacks	= callbacks;
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		if (typeof root !== 'undefined') renderUpdate();
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		return chart;
	};

	return chart;
}

function wdDonutChartDonut() {
	var	color		= d3.scaleOrdinal(d3.schemeCategory10),
		dispatch	= d3.dispatch("itemMouseOver", "itemMouseOut"),
		callbacks	= {},
		data		= [],
		minWidth	= 150,
		minHeight	= 150,
		width		= minWidth,
		height		= minHeight,
		radius		= minHeight/2-3,
		arc		= d3.arc().outerRadius(radius).innerRadius(radius/2).padAngle(0.01).cornerRadius(3),
		arc2		= d3.arc().outerRadius(radius+3).innerRadius(radius/2-3).padAngle(0).cornerRadius(3),
		root, chartLayer, allPies, allPaths, loadtween, updateArcs, renderUpdate;

	function chart(selection) {
		selection.each(function() {
			root		= d3.select(this).attr("width", width).attr("height", height);
			chartLayer	= root.append("g").classed("chartLayer", true);
			allPies		= chartLayer.selectAll(".pies");
			allPaths	= chartLayer.selectAll(".arcPath");
			dispatch.on("itemMouseOver.donut", callbacks["itemMouseOver"]).on("itemMouseOut.donut", callbacks["itemMouseOut"]);
		});
		return chart;
	}
	renderUpdate	= function() {
		root.selectAll("path").remove();
		var 	update	= allPies.data(data),
			arcs	= d3.pie().sort(null).value(function(d) { return d.value; })(data),
			pies	= update.enter().append("g").classed("pies", true)
					.attr("transform", "translate("+[width/2, height/2]+")"),
			blocks	= pies.selectAll(".arc").data(arcs),
			newBlock= blocks.enter().append("g").classed("arc", true);
		newBlock.append("path").classed("arcPath", true).attr("d", arc).attr("stroke", "white").style("stroke-width", "0.5")
			.attr("id", function(d, i) { return "arc-" + i }).attr("fill", "white")
			.on("mouseover", function(d, i) {dispatch.call("itemMouseOver", null, d, i);})
			.on("mouseout", function(d, i) {dispatch.call("itemMouseOut", null, d, i);})
			.transition().duration(350)
			.delay(function(d, i) { return i * 50; })
			.attr("fill", function(d,i){ 
				if (typeof data[i].color !== 'undefined') 
					return data[i].color;
				return color(i);
			}).attrTween("d", loadtween);
		update.exit().remove();
	};
	loadtween	= function(d,i) {
		var interpolate = d3.interpolate(d.startAngle, d.endAngle);
		return function(t) {d.endAngle = interpolate(t);return arc(d);};
	}
	updateArcs	= function() {
		if (typeof allPaths === 'undefined') return;
		radius		= Math.min(width,height)/2-3;
		arc.outerRadius(radius).innerRadius(radius/2);
		arc2.outerRadius(radius+3).innerRadius(radius/2-3);
		allPaths.attr("d", arc);
		allPies.attr("transform", "translate("+[width/2, height/2]+")");
	}
	callbacks["itemMouseOver"]	= function(d, i) {
		if (typeof root === 'undefined') return;
		var c = color(i);
		if (typeof data[i].color !== 'undefined') {c=data[i].color;}
		root.selectAll("#arc-"+i).attr("d",arc2)
	}
	callbacks["itemMouseOut"]	= function(d, i) {
		if (typeof root === 'undefined') return;
		root.selectAll("#arc-"+i).attr("d",arc)
	}

	chart.dispatch	= dispatch;
	chart.callbacks	= callbacks;
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data = _;
		if (typeof root !== 'undefined') renderUpdate();
		return chart;
	};
	chart.height	= function(_) {
		if (!arguments.length) return height;
		height = Math.max(_, minHeight);
		updateArcs();
		if (typeof root !== 'undefined')
			root.attr("height", height);
		return chart;
	};
	chart.width	= function(_) {
		if (!arguments.length) return width;
		width = Math.max(_, minWidth);
		updateArcs();
		if (typeof root !== 'undefined')
			root.attr("width", width);
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		return chart;
	};

	return chart;
}

function wdDonutChart() {
	var	color		= d3.scaleOrdinal(d3.schemeCategory10),
		data		= [],
		legend		= wdDonutChartLegend(),
		donut		= wdDonutChartDonut(),
		width, height, root, rightHtml;

	function chart(selection) {
		selection.each(function() {
			root			= d3.select(this);
			var	rowHtml		= root.append("div").attr("class", "row"),
				leftHtml	= rowHtml.append("div").attr("class", "col-xs-6 col-md-8")
								.append("div").attr("class", "chart-responsive");
			rightHtml		= rowHtml.append("div").attr("class", "col-xs-6 col-md-4");
			rightHtml.append("ul").attr("class", "chart-legend clearfix").call(legend);
			leftHtml.append("svg").call(donut);
			legend.color(color).data(data);
			width			= leftHtml.node().getBoundingClientRect().width;
			height			= rightHtml.node().getBoundingClientRect().height;
			donut.width(width).height(height).color(color).data(data);
			legend.dispatch.on("itemMouseOver.donut",  donut.callbacks["itemMouseOver"]);
			legend.dispatch.on("itemMouseOut.donut",   donut.callbacks["itemMouseOut"]);
			donut.dispatch.on("itemMouseOver.legend", legend.callbacks["itemMouseOver"]);
			donut.dispatch.on("itemMouseOut.legend",  legend.callbacks["itemMouseOut"]);
		});
		return chart;
	}
	chart.data	= function(_) {
		if (!arguments.length) return data;
		data	= _;
		legend.data(data);
		if (typeof rightHtml === 'undefined') return chart;
		height	= rightHtml.node().getBoundingClientRect().height;
		donut.height(height).data(data);
		return chart;
	};
	chart.color	= function(_) {
		if (!arguments.length) return color;
		color = _;
		legend.color(color);
		donut.color(color);
		return chart;
	};

	return chart;
}

function watchedDonut(id, data) {
	var chart = wdDonutChart().data(data);
	d3.select("#"+id).call(chart);
	return chart;
}
