(function(global, factory) {
	if (typeof global.d3 !== 'object' || typeof global.d3.version !== 'string')
		throw new Error('watched requires d3v4');
	var v = global.d3.version.split('.');
	if (v[0] != '4')
		throw new Error('watched requires d3v4');
	if (typeof global.bs !== 'object' || typeof global.bs.version !== 'string')
		throw new Error('watched require d3-Bootstrap');
	if (typeof global.wd !== 'object')
		throw new Error('watched widget require watched componant');

	factory(global.widget = global.widget || {}, global.wd);
})(this, (function(widget, wd) {

function wdBaseWidget() {
	var data = {}, called = false, ready=false, root, box = bs.box();
	function chart(s) { called=true; s.each(chart.init); return chart; }
	chart.dispatch	= d3.dispatch("init", "renderUpdate", "dataUpdate");
	chart.inited	= function() {return called; }
	chart.ready	= function() {return ready; }
	chart.init	= function() { 
		root = d3.select(this);
		chart.dispatch.call("init");
		if (ready) {
			chart.dispatch.call("renderUpdate");
			box.update();
		}
	}
	chart.box	= function() {return box;}
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
		data = _;
		ready=true;
		chart.dispatch.call("dataUpdate");
		if (data.title != 'undefined')
			box.title(data.title).tool({action:'collapse', icon:'fa fa-minus'});
		if (called) {
			chart.dispatch.call("renderUpdate");
			box.update();
		}
		return chart;
	}
	chart.source	= function(_) { 
		if (arguments.length)
			d3.json(_, function(results) { chart.data(results); })
		return chart;
	}
	chart.dispatch.on("init.wdBaseWidget", function() {chart.root().call(box);});

	return chart;
}
widget.table = function() {
	var chart = wdBaseWidget(),body = wd.chart.table();
	chart.box().body(body);
	chart.dispatch.on("dataUpdate.wdTableWidget", function() { 
		body.body(chart.data().body);
		body.heads(chart.data().cols);
	});
	return chart;
}
widget.donut  = function() {
	var chart = wdBaseWidget(),body = wd.chart.donut(), footer = bs.pills();
	chart.box().body(body);
	chart.dispatch.on("dataUpdate.wdDonutWidget", function() { 
		body.data(chart.data().body);
		if (typeof chart.data().footer != 'undefined'  && chart.data().footer.length>0) {
			footer.data(chart.data().footer);
			chart.box().footer(footer);
		}
	});
	return chart;
}
widget.list = function() {
	var chart = wdBaseWidget(),body = bs.list(), title = '';
	chart.box().body(body);
	chart.dispatch.on("dataUpdate.wdListWidget", function() { 
		body.data(chart.data().body);
	});
	return chart;
}
widget.progress = function() {
	var chart = wdBaseWidget(),body = bs.list();
	chart.box().body(body);
	chart.dispatch.on("dataUpdate.wdProgessListWidget", function() {
		chart.data().body.forEach(function (d) {
			body.add(bs.progress().title(d.title).url(d.url).data(d.items))
		});
	});
	return chart;
}
widget.properties = function() {
	var chart = wdBaseWidget(),body = bs.descTable();
	chart.box().body(body);
	chart.dispatch.on("dataUpdate.wdPropertyWidget", function() { 
		body.data(chart.data().body);
	});
	return chart;
}
widget.memSwap = function() {
	var chart = wdBaseWidget(),body = wd.chart.memBar();
	chart.box().body(body);
	chart.dispatch.on("dataUpdate.wdMemSwapWidget", function() {
		body.data(chart.data().body);
	});
	return chart;
}
widget.gfxRessource = function() {
	var chart = wdBaseWidget(),body = wd.chart.gfx(), legend = wd.chart.gfxLegend(), footer = wd.chart.timeline();
	chart.box().body(body).tool(legend).footer(footer);
	body.legend(legend);body.timeline(footer);footer.legend(legend);
	chart.dispatch.on("dataUpdate.wdRessourceGfxWidget", function() {
		legend.data(chart.data().cols);
		body.data(chart.data().data);
		footer.data(chart.data().data);
		chart.box().title(chart.data().src.obj_name+' - '+chart.data().src.res_name);
	});
	return chart;
}
widget.gfxAvailability = function() {
	var chart = wdBaseWidget(),body = wd.chart.gfx(), title = '', legend = wd.chart.gfxAvailLegend();
	body.legend(legend);
	body.areaSet('',true);
	chart.box().body(body).tool(legend);
	chart.title = function(_) {if (!arguments.length) return title;title=_;return chart;}
	chart.dispatch.on("dataUpdate.gfxAvailability", function() {
		body.data(chart.data());
	});
	chart.dispatch.on("renderUpdate.gfxAvailability", function() {
		chart.box().title(title);
	});
	return chart;
}
widget.gfxEvent = function() {
	var chart = wdBaseWidget(),body = wd.chart.gfx(), title = '', legend = wd.chart.gfxLegend();
	body.legend(legend);
	chart.box().body(body).tool(legend);
	chart.title = function(_) {if (!arguments.length) return title;title=_;return chart;}
	chart.prop = function(_) {if (!arguments.length) return legend.prop(); legend.prop(_);return chart;}
	chart.dispatch.on("dataUpdate.gfxEvent", function() {
		legend.data(chart.data().cols);
		body.data(chart.data().data);
	});
	chart.dispatch.on("renderUpdate.gfxEvent", function() {
		chart.box().title(title);
	});
	return chart;
}

}));
