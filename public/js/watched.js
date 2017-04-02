/*
 * Shell helper:
 *	look() { find public/js/ -name '*.js' -exec grep -nH "$1" {} \; ;find templates/ -name '*.twig' -exec grep -nH "$1" {} \; ; }
 *	plook() { grep -nH "$1" public/index.php;find classes/ -name '*.php' -exec grep -nH "$1" {} \; ;find templates/ -name '*.twig' -exec grep -nH "$1" {} \; ; }
 */
/////////////////////////////////////////////////////////////////////////////////////////////
(function(global, factory) {
	if (typeof global.d3 !== 'object' || typeof global.d3.version !== 'string')
		throw new Error('watched requires d3v4');
	var v = global.d3.version.split('.');
	if (v[0] != '4')
		throw new Error('watched requires d3v4');
	if (typeof global.bs !== 'object' || typeof global.bs.version !== 'string')
		throw new Error('watched require d3-Bootstrap');
	
	factory(global.wd = global.wd || {}, d3, global);
})(this, (function(wd, d3, global) {
wd.format = wd.format || {}
wd.componant = wd.componant || {}
wd.chart = wd.chart || {}

d3.dispatch.prototype.register = function() {
	for (var i = 0, n = arguments.length, _ = {}, t; i < n; ++i) {
		if (!(t = arguments[i] + "") || (t in this._)) throw new Error("illegal type: " + t);
		this._[t] = [];
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////
// watched Common Component
wd.format.date		= function(date) {
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
wd.format.number	= bs.api.format.number
wd.componant.base	= function() {
	var	data	= [],
		called	= false,
		ready	= false,
		root;
	function chart(s) { called=true; s.each(chart.init); return chart; }

	chart.callbacks = {};
	chart.dispatch	= d3.dispatch("init", "renderUpdate", "dataUpdate");
	chart.inited	= function() {return called; }
	chart.ready	= function() {return ready; }
	chart.init	= function() { 
		root = d3.select(this);
		chart.dispatch.call("init");
		if (ready)
			chart.dispatch.call("renderUpdate");
	}
	chart.onTop = function(s) {
		return s.each(function(){
			this.parentNode.appendChild(this);
		});
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
		ready=true;
		chart.dispatch.call("dataUpdate");
		if (chart.inited())
			chart.dispatch.call("renderUpdate");
		return chart;
	}
	return chart;
}
wd.componant.colored	= function(pClass, pColor) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wd.componant.base(),
		color	= (typeof pColor!="undefined"&&pColor!=null)?pColor:d3.scaleOrdinal(d3.schemeCategory10);
	chart.dispatch.register("colorUpdate");
	chart.color	= function(_) { 
		if (!arguments.length) return color; color = _;
		chart.dispatch.call("colorUpdate");
		return chart;
	}
	return chart;
}
wd.componant.sized	= function(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wd.componant.base(),
		width	= (typeof pW!="undefined"&&pW!=null)?pW:0, 
		height	= (typeof pH!="undefined"&&pH!=null)?pH:0;
	chart.dispatch.register("heightUpdate","widthUpdate");
	chart.width	= function(_) { 
		if (!arguments.length) return width; width = _;
		chart.dispatch.call("widthUpdate");
		return chart;
	}
	chart.height	= function(_) { 
		if (!arguments.length) return height; height = _;
		chart.dispatch.call("heightUpdate");
		return chart;
	}
	return chart;
}
wd.componant.minSized	= function(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wd.componant.sized(null, pW, pH),
		minWidth	= (typeof pW!="undefined"&&pW!=null)?pW:0, 
		minHeight	= (typeof pH!="undefined"&&pH!=null)?pH:0,
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
wd.componant.axed	= function(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wd.componant.sized(null, pW, pH);
	chart.xAxis		= d3.scaleTime().range([0, chart.width()]);
	chart.yAxis		= d3.scaleLinear().range([chart.height(), 0]);
	chart.dispatch.on("widthUpdate.wd.componant.axed", function() { 
		chart.xAxis.range([0, chart.width()]);
	});
	chart.dispatch.on("heightUpdate.wd.componant.axed", function() { 
		chart.yAxis.range([chart.height(), 0]);
	});
	chart.dispatch.on("dataUpdate.wd.componant.axed", function() { 
		chart.xAxis.domain(d3.extent(chart.data(), function(d) { return d.timestamp; }));
		chart.yAxis.domain([0, d3.max(chart.data(), function(d) {
			var keys;
			if (typeof chart.filter !="undefined")
				keys = Object.keys(d).filter(chart.filter());
			else
				keys = Object.keys(d);
			var vals = keys.map(function (i) {return d[i]});
			return d3.max(vals);
		})]);
	});
	return chart;
}
wd.componant.axes	= function(pClass, pW, pH) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wd.componant.axed(null, pW, pH);
	chart.xAxisLine		= function(g) {
		g.call(d3.axisBottom(chart.xAxis).tickFormat(wd.format.date));
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
	chart.dispatch.on("init.wd.componant.axes", function() {
		chart.root().append("g").attr("class", "x axis").attr("transform", "translate(0," + chart.height() + ")").call(chart.xAxisLine);
		chart.root().append("g").attr("class", "y axis").call(chart.yAxisLine);
	});
	chart.dispatch.on("heightUpdate.wd.componant.axes", function() { 
		if (chart.inited())
			chart.root().select(".x.axis").attr("transform", "translate(0," + chart.height() + ")");
	});
	chart.dispatch.on("renderUpdate.wd.componant.axes", function() { 
		var	update	= chart.root().transition();
		update.select(".x.axis").duration(150).call(chart.xAxisLine);
		update.select(".y.axis").duration(150).call(chart.yAxisLine);
	});
	return chart;
}
}));
