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

function wdBaseComponant() {
	var	data	= [],
		called	= false,
		root;
	function chart(s) { called=true; s.each(chart.init); return chart; }

	chart.callbacks = {};
	chart.inited	= function() {return called; }
	chart.init	= function() { 
		root = d3.select(this); 
		if (typeof chart.onInit === 'function')
			chart.onInit();
		if (typeof chart.renderUpdate === 'function')
			chart.renderUpdate();
	}
	chart.root	= function(_) {
		if (arguments.length) {
			root = _;
			return chart;
		} else if (chart.inited())
			return root; 
		else
			return false;
	}
	chart.data	= function(_) { 
		if (!arguments.length) return data;
		if (typeof _!="object" || typeof _[0]=="undefined") return chart;
		data = _;
		if (typeof chart.onDataUpdate === 'function')
			chart.onDataUpdate();
		if (chart.inited() && typeof chart.renderUpdate === 'function')
			chart.renderUpdate();
		return chart;
	}
	return chart;
}

function wdFilteredComponant(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdBaseComponant(),
		keys	= [],
 		pData	= chart.data,
		filter	= function(e){return e!="timestamp";};

	chart.filter	= function(_) { if (!arguments.length) return filter; filter = _; return chart; }
	chart.keys	= function(_) { 
		if (!arguments.length) return keys;
		keys = _;
	}
	chart.data	= function(_) { 
		if (!arguments.length) return pData();
		if (typeof _!="object" || typeof _[0]=="undefined") return chart;
		chart.keys(Object.keys(_[0]).filter(filter))
		return 	pData(_);
	}
	return chart;
}

function wdColoredComponant(pClass, pColor) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdBaseComponant(),
		color	= (typeof pColor!="undefined"&&pColor!=null)?pColor:d3.scaleOrdinal(d3.schemeCategory10);
	chart.color	= function(_) { 
		if (!arguments.length) return color; color = _;
		if (typeof chart.onColorUpdate === 'function')
			chart.onColorUpdate();
		return chart;
	}
	return chart;
}

function wdSizedComponant(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdBaseComponant(),
		width	= (typeof pW!="undefined"&&pW!=null)?pW:0, 
		height	= (typeof pH!="undefined"&&pH!=null)?pH:0;
	chart.width	= function(_) { 
		if (!arguments.length) return width; width = _;
		if (typeof chart.onWidthUpdate === 'function')
			chart.onWidthUpdate();
		return chart;
	}
	chart.height	= function(_) { 
		if (!arguments.length) return height; height = _;
		if (typeof chart.onHeightUpdate === 'function')
			chart.onHeightUpdate();
		return chart;
	}
	return chart;
}

function wdMinSizedComponant(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdSizedComponant(null, pW, pH),
		minWidth	= (typeof pW!="undefined"&&pW!=null)?pW:0, 
		minHeight	= (typeof pH!="undefined"&&pH!=null)?pH:0
		pWidth		= chart.width,
		pHeight		= chart.height;

	chart.updateSizeFromMin	=function () {pWidth(minWidth);pHeight(minHeight)}
	chart.width	= function(_) { 
		if (!arguments.length) return pWidth(); 
		if (_>minWidth) return pWidth(_);
		return chart;
	}
	chart.height	= function(_) { 
		if (!arguments.length) return pHeight(); 
		if (_>minHeight) return pHeight(_);
		return chart;
	}
	return chart;
}

function wdAxedComponant(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdSizedComponant(null, pW, pH);
	chart.xAxis		= d3.scaleTime().range([0, chart.width()]);
	chart.yAxis		= d3.scaleLinear().range([chart.height(), 0]);
	chart.onHeightUpdate	= function(_) {
		chart.yAxis.range([chart.height(), 0]);
	};
	chart.onWidthUpdate	= function() {
		chart.xAxis.range([0, chart.width()]);
	};
	chart.onDataUpdate	= function() {
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
			var keys = Object.keys(d).filter(chart.filter()),
			    vals = keys.map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
	};
	return chart;
}

function wdAxesComponant(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdAxedComponant(null, pW, pH), 
		pOnHU = chart.onHeightUpdate, pOnWU = chart.onWidthUpdate;
	chart.xAxisLine		= function(g) {
		g.call(d3.axisBottom(chart.xAxis).tickFormat(wdDateFormat));
		g.select(".domain").remove();
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1.5px");
	}
	chart.yAxisLine		= function(g) {
		g.call(d3.axisRight(chart.yAxis).tickSize(chart.width()));
		g.select(".domain").remove();
		g.selectAll(".tick line").attr("stroke", "lightgrey").style("stroke-width", "1px");
		g.selectAll(".tick:not(:first-of-type) line").attr("stroke-dasharray", "5,5");
		g.selectAll(".tick text").attr("x", -20);
	};
	chart.onInit		= function () {
		chart.root().append("g").attr("class", "x axis").attr("transform", "translate(0," + chart.height() + ")").call(chart.xAxisLine);
		chart.root().append("g").attr("class", "y axis").call(chart.yAxisLine);
	}
	chart.onDataUpdate	= function() {
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
			var keys = Object.keys(d),
			    vals = keys.map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
	};
	chart.renderUpdate	= function() {
		var	update	= chart.root().transition();
		update.select(".x.axis").duration(150).call(chart.xAxisLine);
		update.select(".y.axis").duration(150).call(chart.yAxisLine);
	}
	chart.onWidthUpdate	= function() {
		pOnWU();
		if (chart.inited()) chart.renderUpdate;
	};
	chart.onHeightUpdate	= function() {
		pOnHU();
		if (chart.inited())
			root.select(".x.axis").attr("transform", "translate(0," + chart.height() + ")");
	}
	return chart;
}

function wdHLegendComponant(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdSizedComponant( wdColoredComponant( wdFilteredComponant()), 500,30);

	chart.onDataUpdate	= function() {
	}
	chart.renderUpdate	= function() {
		var	update	= chart.root().transition();
	};
	chart.callbacks["mouseMove"]	= function(x,y) {
	}
	return chart;
}

/////////////////////////////////////////////////////////////////////////////////////////////
// watchedEvent

function wdEventLines(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdFilteredComponant( wdAxedComponant(null, 500,200))),
		line		= d3.line().curve(d3.curveBasis)
					.x(function(d) { return chart.xAxis(d.timestamp); })
					.y(function(d) { return chart.yAxis(d.value); }),
		lines		= [];
		pOnDU = chart.onDataUpdate;

	chart.renderUpdate	= function() {
		chart.root().selectAll(".lines").remove();
		var	update	= chart.root().selectAll(".lines").data(lines, function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines");
		update.exit().remove();
		eLines.append("path").attr("class", "line")
			.attr("d", function(d) { return line(d.values); })
			.style("stroke", function(d) { return chart.color()(d.id); });
	};
	chart.callbacks["mouseMove"]	= function(x,y) {
	};
	chart.onDataUpdate	= function() {
		pOnDU();
		lines = chart.keys().map(function(i) {
			return {
				id: i,
				values: chart.data().map(function(d) {
					return {timestamp: d.timestamp, value:+d[i]};
				})
			};
		});
		chart.color().domain(chart.keys());
	};

	return chart;
}

function wdEventAxes(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdFilteredComponant( wdAxesComponant( null,500,200)),
		marq, pInit = chart.onInit;

	chart.onInit	= function() {
		pInit();
		marq	= chart.root().append("line").attr("x1", 0).attr("y1", 0)
				.attr("x2", 0).attr("y2", chart.height())
				.attr("shape-rendering", "crispEdges")
				.style("stroke-width", 1).style("stroke", "black")
				.style("fill", "none");
	}
	chart.callbacks["mouseMove"]	= function(x,y) { if (typeof marq !== 'undefined') marq.attr("x1", x).attr("x2", x); };
	chart.onDataUpdate	= function() {
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
			var keys = Object.keys(d).filter(chart.filter()),
			    vals = keys.map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
	};
	return chart;
}

function wdEventChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass: wdColoredComponant( wdFilteredComponant(wdMinSizedComponant(null, 500,380))),
		margin		= {top: 50, right: 10, bottom: 20, left: 30},
		dispatch	= d3.dispatch("mouseMove"),
		axes		= wdEventAxes(),
		legend 		= wdHLegendComponant().color(chart.color()),
		lines		= wdEventLines().color(chart.color()),
		baseUrl		= "",
		prop		= "";

	chart.onInit	= function() {
		var bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width);
		chart.height(bound.height);
		legend.height(margin.top - 2*margin.right)
		var svg		= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height());
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(lines);
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(axes);
		svg.append("g").attr("transform", "translate(" + margin.right + "," + margin.right + ")").call(legend);
		svg.on("mousemove", function() {
			var 	bBox	= chart.root().node().getBoundingClientRect();
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
	}
	chart.onHeightUpdate	= function() {
		axes.height(chart.height() - margin.top - margin.bottom);
		lines.height(chart.height() - margin.top - margin.bottom);
	};
	chart.onWidthUpdate	= function() {
		axes.width(chart.width() - margin.left - margin.right);
		lines.width(chart.width() - margin.left - margin.right);
		legend.width(chart.width() - 2*margin.right);
	};
	chart.onColorUpdate	= function() {
		lines.color(chart.color());
		legend.color(chart.color());
	};
	chart.onDataUpdate	= function() {
		axes.data(chart.data());
		lines.data(chart.data());
		legend.data(chart.data());
	};
	chart.dispatch	= dispatch;
	chart.prop	= function(_) {
		if (!arguments.length) return prop; prop = _;
		chart.filter(function(e){return e!="timestamp" && (e.match("avg_"+prop) || e==prop );});
		axes.filter(chart.filter());
		lines.filter(chart.filter());
		return chart;
	};
	chart.baseUrl	= function(_) {
		if (!arguments.length) return chart.data(); baseUrl = _;
		$.getJSON(baseUrl, function(results) {
			chart.data(results); 
		});

		return chart;
	};
	chart.updateSizeFromMin();
	return chart;
}

function watchedEvent(id, baseUrl, prop) {
	var chart = wdEventChart().prop(prop).baseUrl(baseUrl);
	d3.select("#"+id).call(chart);
	return chart;
}
/////////////////////////////////////////////////////////////////////////////////////////////
// watchedService
function wdServiceAreas(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdFilteredComponant( wdAxedComponant(null, 500, 300))),
		stack		= d3.stack(),
		area		= d3.area()
					.x(function(d, i) { return chart.xAxis(d.data.timestamp); })
					.y0(function(d) { return chart.yAxis(d[0]); })
					.y1(function(d) { return chart.yAxis(d[1]); });

	chart.renderUpdate	= function() {
		chart.root().selectAll(".lines").remove();
		var	update	= chart.root().selectAll(".lines").data(stack(chart.data()), function(d) { return d.timestamp }),
			eLines	= update.enter().append("g").attr("class", "lines");
		update.exit().remove();
		eLines.append("path").attr("class", "area")
			.style("fill", function(d) { return chart.color()(d.key); })
			.attr("d", area);
	};
	chart.onDataUpdate	= function() {
		stack.keys(chart.keys());
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) { return d.failed+d.missing+d.ok; })]);
	};

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

	chart.onDataUpdate	= function() {
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) { return d.failed+d.missing+d.ok; })]);
	};

	return chart;
}

function wdServiceChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdMinSizedComponant(null,500,330),
		margin		= {top: 10, right: 10, bottom: 20, left: 30},
		color		= function (k) {
			switch(k) {
				case "missing": return '#ff851b';
				case "failed": return '#dd4b39';
				default: return '#b3ffb3';
			}
		},
		axes 		= wdServiceAxes(),
		areas		= wdServiceAreas().color(color),
		baseUrl		= "";

	chart.onInit	= function() {
		var	bound	= chart.root().node().getBoundingClientRect();
		chart.width(bound.width);
		chart.height(bound.height);
		var 	svg	= chart.root().append("svg").attr("width", chart.width()).attr("height", chart.height());
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(areas);
		svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")").call(axes);
	}
	chart.onDataUpdate	= function(_) {
		axes.data(chart.data());
		areas.data(chart.data());
	}
	chart.setPeriod	= function(p) {
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
			chart.data(results); 
		});
	}
	chart.onHeightUpdate	= function(_) {
		 axes.height(chart.height() - margin.top - margin.bottom);
		areas.height(chart.height() - margin.top - margin.bottom);
	}
	chart.onWidthUpdate	= function(_) {
		 axes.width(chart.width() - margin.left - margin.right);
		areas.width(chart.width() - margin.left - margin.right);
	};
	chart.baseUrl	= function(_) {
		if (!arguments.length) return data; baseUrl = _;
		chart.setPeriod();
		return chart;
	};
	chart.updateSizeFromMin();
	chart.color	= function(_) { if (!arguments.length) return color; color = _; return chart; };

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

function wdDonutChartLegend(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant();

	chart.dispatch	= d3.dispatch("itemMouseOver", "itemMouseOut");
	chart.callbacks["itemMouseOver"]	= function(d, i) {
		if (!chart.inited()) return;
		var c = chart.color()(i);
		if (typeof chart.data()[i].color !== 'undefined') {c=chart.data()[i].color;}
		chart.root().selectAll("#li-"+i)
			.style("background-color", c)
			.style("font-weight","bold")
	};
	chart.callbacks["itemMouseOut"]	= function(d, i) {
		if (!chart.inited()) return;
		chart.root().selectAll("#li-"+i)
			.style("background-color", "white")
			.style("font-weight","normal")
	};
	chart.onInit	= function() {
		chart.dispatch	.on("itemMouseOver.legend", chart.callbacks["itemMouseOver"])
				.on("itemMouseOut.legend",  chart.callbacks["itemMouseOut"]);
	}
	chart.renderUpdate	= function() {
		chart.root().selectAll("li").selectAll("i").remove();
		chart.root().selectAll("li").selectAll("span").remove();
		var	update	= chart.root().selectAll("li").data(chart.data(), function(d) { return d ? d.label : this.id; }),
			liHtml	= update.enter().append("li")
					.merge(update).attr("id", function(d, i) { return "li-" + i });
		liHtml.append("i").attr("class", "fa fa-circle-o")
			.attr("style", function (d,i) { 
				if (typeof d.color !== 'undefined') 
					return "color:"+d.color+";";
				return "color:"+chart.color()(i)+";";
			});
		liHtml.append("span").text(function (d) {return " "+d.label;});
		liHtml.append("span").attr("class", "pull-right").text(function (d) {return d.value;});
		liHtml	.on("mouseover", function(d, i){chart.dispatch.call("itemMouseOver", null, d, i);})
			.on("mouseout", function(d, i) {chart.dispatch.call("itemMouseOut",  null, d, i);})
		update.exit().remove();
	};

	return chart;
}

function wdDonutChartDonut(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdMinSizedComponant( null, 150,150)),
		radius	= chart.width()/2-3,
		arc	= d3.arc().outerRadius(radius).innerRadius(radius/2).padAngle(0.01).cornerRadius(3),
		arc2	= d3.arc().outerRadius(radius+3).innerRadius(radius/2-3).padAngle(0).cornerRadius(3),
		allPies, allPaths;

	chart.dispatch	= d3.dispatch("itemMouseOver", "itemMouseOut");
	chart.callbacks["itemMouseOver"] = function(d, i) {
		if (!chart.inited()) return;
		var c = chart.color()(i);
		if (typeof chart.data()[i].color !== 'undefined') {c=chart.data()[i].color;}
		chart.root().selectAll("#arc-"+i).attr("d",arc2)
	}
	chart.callbacks["itemMouseOut"]	= function(d, i) {
		if (!chart.inited()) return;
		chart.root().selectAll("#arc-"+i).attr("d",arc)
	}
	chart.onInit	= function() {
		chart.root().attr("width", chart.width()).attr("height", chart.height());
		var chartLayer	= chart.root().append("g").classed("chartLayer", true);
		allPies		= chartLayer.selectAll(".pies");
		allPaths	= chartLayer.selectAll(".arcPath");
		chart.dispatch	.on("itemMouseOver.donut", chart.callbacks["itemMouseOver"])
				.on("itemMouseOut.donut",  chart.callbacks["itemMouseOut"]);
	}
	chart.loadtween	= function(d,i) {
		var interpolate = d3.interpolate(d.startAngle, d.endAngle);
		return function(t) {d.endAngle = interpolate(t);return arc(d);};
	}
	chart.renderUpdate	= function() {
		chart.root().selectAll("path").remove();
		var 	update	= allPies.data(chart.data()),
			arcs	= d3.pie().sort(null).value(function(d) { return d.value; })(chart.data()),
			pies	= update.enter().append("g").classed("pies", true)
					.attr("transform", "translate("+[chart.width()/2, chart.height()/2]+")"),
			blocks	= pies.selectAll(".arc").data(arcs),
			newBlock= blocks.enter().append("g").classed("arc", true);
		newBlock.append("path").classed("arcPath", true).attr("d", arc).attr("stroke", "white").style("stroke-width", "0.5")
			.attr("id", function(d, i) { return "arc-" + i }).attr("fill", "white")
			.on("mouseover", function(d, i) {chart.dispatch.call("itemMouseOver", null, d, i);})
			.on("mouseout", function(d, i) { chart.dispatch.call("itemMouseOut",  null, d, i);})
			.transition().duration(350)
			.delay(function(d, i) { return i * 50; })
			.attr("fill", function(d,i){ 
				if (typeof chart.data()[i].color !== 'undefined') 
					return chart.data()[i].color;
				return chart.color()(i);
			}).attrTween("d", chart.loadtween);
		update.exit().remove();
	};
	chart.updateArcs	= function() {
		if (!chart.inited()) return;
		radius		= Math.min(chart.width(),chart.height())/2-3;
		arc.outerRadius(radius).innerRadius(radius/2);
		arc2.outerRadius(radius+3).innerRadius(radius/2-3);
		allPaths.attr("d", arc);
		allPies.attr("transform", "translate("+[chart.width()/2, chart.height()/2]+")");
	}

	chart.onHeightUpdate	= function(_) {
		chart.updateArcs();
		if (chart.inited())
			chart.root().attr("height", chart.height());
	};
	chart.onWidthUpdate	= function(_) {
		chart.updateArcs();
		if (chart.inited())
			chart.root().attr("width", chart.width());
	};

	return chart;
}

function wdDonutChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant(),
		legend	= wdDonutChartLegend(),
		donut	= wdDonutChartDonut(),
		width, height, rightHtml;

	chart.onInit	= function() {
		var	rowHtml	= chart.root().append("div").attr("class", "row"),
			leftHtml= rowHtml.append("div").attr("class", "col-xs-6 col-md-8")
					.append("div").attr("class", "chart-responsive");
		rightHtml	= rowHtml.append("div").attr("class", "col-xs-6 col-md-4");
		rightHtml.append("ul").attr("class", "chart-legend clearfix").call(legend);
		leftHtml.append("svg").call(donut);
		legend.color(chart.color()).data(chart.data());
		width		= leftHtml.node().getBoundingClientRect().width;
		height		= rightHtml.node().getBoundingClientRect().height;
		donut.width(width).height(height).color(chart.color()).data(chart.data());
		legend.dispatch.on("itemMouseOver.donut",  donut.callbacks["itemMouseOver"]);
		legend.dispatch.on("itemMouseOut.donut",   donut.callbacks["itemMouseOut"]);
		donut.dispatch.on("itemMouseOver.legend", legend.callbacks["itemMouseOver"]);
		donut.dispatch.on("itemMouseOut.legend",  legend.callbacks["itemMouseOut"]);
	}
	chart.renderUpdate	= function() {
		height	= rightHtml.node().getBoundingClientRect().height;
		donut.height(height).data(chart.data());
	};
	chart.onColorUpdate	= function() {
		legend.color(chart.color());
		donut.color(chart.color());
	};

	return chart;
}

function watchedDonut(id, data) {
	var chart = wdDonutChart().data(data);
	d3.select("#"+id).call(chart);
	return chart;
}
