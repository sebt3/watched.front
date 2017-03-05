function wdDonutChartLegend(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant();

	chart.dispatch.register("itemMouseOver","itemMouseOut");
	chart.callbacks.itemMouseOver	= function(d, i) {
		if (!chart.inited()) return;
		var c = chart.color()(i);
		if (typeof chart.data()[i].color !== 'undefined') {c=chart.data()[i].color;}
		chart.root().selectAll("#li-"+i)
			.style("background-color", c)
			.style("font-weight","bold")
	};
	chart.callbacks.itemMouseOut	= function(d, i) {
		if (!chart.inited()) return;
		chart.root().selectAll("#li-"+i)
			.style("background-color", "white")
			.style("font-weight","normal")
	};
	chart.dispatch.on("init.wdDonutChartLegend", function() { 
		chart.dispatch	.on("itemMouseOver.legend", chart.callbacks.itemMouseOver)
				.on("itemMouseOut.legend",  chart.callbacks.itemMouseOut);
	});
	chart.dispatch.on("renderUpdate.wdDonutChartLegend", function() { 
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
		liHtml.append("span").attr("class", "pull-right").text(function (d) {if(typeof d.value == 'number') return wdNumberFormat(d.value);return d.value;});
		liHtml	.on("mouseover", function(d, i){chart.dispatch.call("itemMouseOver", null, d, i);})
			.on("mouseout", function(d, i) {chart.dispatch.call("itemMouseOut",  null, d, i);})
		update.exit().remove();
	});

	return chart;
}

function wdDonutChartDonut(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant( wdMinSizedComponant( null, 150,150)),
		radius	= chart.width()/2-3,
		arc	= d3.arc().outerRadius(radius).innerRadius(radius/2).padAngle(0.01).cornerRadius(3),
		arc2	= d3.arc().outerRadius(radius+3).innerRadius(radius/2-3).padAngle(0).cornerRadius(3),
		allPies, allPaths;

	chart.dispatch.register("itemMouseOver","itemMouseOut");
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
	chart.updateArcs	= function() {
		if (!chart.inited()) return;
		radius		= Math.min(chart.width(),chart.height())/2-3;
		arc.outerRadius(radius).innerRadius(radius/2);
		arc2.outerRadius(radius+3).innerRadius(radius/2-3);
		allPaths.attr("d", arc);
		allPies.attr("transform", "translate("+[chart.width()/2, chart.height()/2]+")");
	}
	chart.loadtween	= function(d,i) {
		var interpolate = d3.interpolate(d.startAngle, d.endAngle);
		return function(t) {d.endAngle = interpolate(t);return arc(d);};
	}
	chart.dispatch	.on("itemMouseOver.donut", chart.callbacks["itemMouseOver"])
			.on("itemMouseOut.donut",  chart.callbacks["itemMouseOut"]);
	chart.dispatch.on("init.wdDonutChartDonut", function() { 
		chart.root().attr("width", chart.width()).attr("height", chart.height());
		var chartLayer	= chart.root().append("g").classed("chartLayer", true);
		allPies		= chartLayer.selectAll(".pies");
		allPaths	= chartLayer.selectAll(".arcPath");
	});
	chart.dispatch.on("renderUpdate.wdDonutChartDonut", function() { 
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
	});
	chart.dispatch.on("heightUpdate.wdDonutChartDonut", function() { 
		chart.updateArcs();
		if (chart.inited())
			chart.root().attr("height", chart.height());
	});
	chart.dispatch.on("widthUpdate.wdDonutChartDonut", function() { 
		chart.updateArcs();
		if (chart.inited())
			chart.root().attr("width", chart.width());
	});

	return chart;
}

function wdDonutChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:wdColoredComponant(),
		legend	= wdDonutChartLegend(),
		donut	= wdDonutChartDonut(),
		width, height, rightHtml;

	chart.dispatch.on("init.wdDonutChart", function() { 
		var	rowHtml	= chart.root().append("div").attr("class", "row"),
			leftHtml= rowHtml.append("div").attr("class", "col-xs-12 col-sm-8 col-md-12 col-lg-7")
					.append("div").attr("class", "chart-responsive");
		rightHtml	= rowHtml.append("div").attr("class", "col-xs-12 col-sm-4 col-md-12 col-lg-5");
		rightHtml.append("ul").attr("class", "list-unstyled clearfix").call(legend);
		leftHtml.append("svg").call(donut);
		legend.color(chart.color()).data(chart.data());
		width		= leftHtml.node().getBoundingClientRect().width;
		height		= rightHtml.node().getBoundingClientRect().height;
		donut.width(width).height(height).color(chart.color()).data(chart.data());
		legend.dispatch.on("itemMouseOver.donut",  donut.callbacks["itemMouseOver"]);
		legend.dispatch.on("itemMouseOut.donut",   donut.callbacks["itemMouseOut"]);
		donut.dispatch.on("itemMouseOver.legend", legend.callbacks["itemMouseOver"]);
		donut.dispatch.on("itemMouseOut.legend",  legend.callbacks["itemMouseOut"]);
	});
	chart.dispatch.on("renderUpdate.wdDonutChart", function() { 
		height	= rightHtml.node().getBoundingClientRect().height;
		donut.height(height).data(chart.data());
	});
	chart.dispatch.on("colorUpdate.wdDonutChart", function() { 
		legend.color(chart.color());
		donut.color(chart.color());
	});

	return chart;
}

function watchedDonut(id, data) {
	var chart = wdDonutChart().data(data);
	d3.select("#"+id).call(chart);
	return chart;
}

