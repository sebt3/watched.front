function wdBaseWidget() {
	var data = {}, called = false, ready=false, root;
	function chart(s) { called=true; s.each(chart.init); return chart; }
	chart.dispatch	= d3.dispatch("init", "renderUpdate", "dataUpdate");
	chart.inited	= function() {return called; }
	chart.ready	= function() {return ready; }
	chart.init	= function() { 
		root = d3.select(this);
		chart.dispatch.call("init");
		if (ready)
			chart.dispatch.call("renderUpdate");
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
		data = _;
		ready=true;
		chart.dispatch.call("dataUpdate");
		if (chart.inited())
			chart.dispatch.call("renderUpdate");
		return chart;
	}
	chart.source	= function(_) { 
		if (arguments.length)
			d3.json(_, function(results) { chart.data(results); })
		return chart;
	}

	return chart;
}
function wdTableWidget() {
	var chart = wdBaseWidget(),body = wdTableChart(), title = '';
	chart.dispatch.on("dataUpdate.wdTableWidget", function() { 
		body.body(chart.data().body);
		body.heads(chart.data().cols);
		if (chart.data().title != 'undefined')
			title = chart.data().title;
	});
	chart.dispatch.on("renderUpdate.wdTableWidget", function() {
		var b = wdBox().body(body);
		if (chart.data().title != 'undefined')
			b.title(title).tool({action:'collapse', icon:'fa fa-minus'})
		chart.root().select('div').remove();
		chart.root().call(b);
	});
	return chart;
}
function wdDonutWidget() {
	var chart = wdBaseWidget(),body = wdDonutChart(), title = '', footer = wdPill();
	chart.dispatch.on("dataUpdate.wdDonutWidget", function() { 
		body.data(chart.data().body);
		title = chart.data().title;
		if (typeof chart.data().footer != 'undefined')
			footer.data(chart.data().footer);
	});
	chart.dispatch.on("renderUpdate.wdDonutWidget", function() {
		var b = wdBox().title(title)
			.tool({action:'collapse', icon:'fa fa-minus'})
			.body(body);
		if (typeof chart.data().footer != 'undefined' && chart.data().footer.length>0)
			b.footer(footer);
		chart.root().select('div').remove();
		chart.root().call(b);
	});
	return chart;
}
function wdListWidget() {
	var chart = wdBaseWidget(),body = wdList(), title = '';
	chart.dispatch.on("dataUpdate.wdListWidget", function() { 
		body.data(chart.data().body);
		title = chart.data().title;
	});
	chart.dispatch.on("renderUpdate.wdListWidget", function() {
		chart.root().select('div').remove();
		chart.root().call(wdBox().title(title)
			.tool({action:'collapse', icon:'fa fa-minus'})
			.body(body)
		);
	});
	return chart;
}
function wdProgessListWidget() {
	var chart = wdBaseWidget(),body = wdList(), title = '';
	chart.dispatch.on("dataUpdate.wdProgessListWidget", function() {
		chart.data().body.forEach(function (d){
			body.add(wdProgess().title(d.title).url(d.url).data(d.items))
		});
		title = chart.data().title;
	});
	chart.dispatch.on("renderUpdate.wdProgessListWidget", function() {
		chart.root().select('div').remove();
		chart.root().call(wdBox().title(title)
			.tool({action:'collapse', icon:'fa fa-minus'})
			.body(body)
		);
	});
	return chart;
}
function wdPropertyWidget() {
	var chart = wdBaseWidget(),body = wdDescTable(), title = '';
	chart.dispatch.on("dataUpdate.wdPropertyWidget", function() { 
		body.data(chart.data().body);
		title = chart.data().title;
	});
	chart.dispatch.on("renderUpdate.wdPropertyWidget", function() {
		chart.root().select('div').remove();
		chart.root().call(wdBox().title(title)
			.tool({action:'collapse', icon:'fa fa-minus'})
			.body(body)
		);
	});
	return chart;
}
function wdMemSwapWidget() {
	var chart = wdBaseWidget(),body = wdMemSwapChart(), title = '';
	chart.dispatch.on("dataUpdate.wdMemSwapWidget", function() {
		body.data(chart.data().body);
		title = chart.data().title;
	});
	chart.dispatch.on("renderUpdate.wdMemSwapWidget", function() {
		chart.root().select('div').remove();
		chart.root().call(wdBox().title(title)
			.tool({action:'collapse', icon:'fa fa-minus'})
			.body(body)
		);
	});
	return chart;
}

function wdRessourceGfxWidget() {
	var chart = wdBaseWidget(),body = wdGfxChart(), title = '', legend = wdGfxLegend(), footer = wdGfxTimeLine();
	body.legend(legend);body.timeline(footer);legend.gfx(body);footer.legend(legend);
	chart.dispatch.on("dataUpdate.wdRessourceGfxWidget", function() {
		legend.data(chart.data().cols);
		body.data(chart.data().data);
		footer.data(chart.data().data);
		title = chart.data().src.obj_name+' - '+chart.data().src.res_name;
	});
	chart.dispatch.on("renderUpdate.wdRessourceGfxWidget", function() {
		chart.root().select('div').remove();
		chart.root().call(wdBox().title(title)
			.tool(legend)
			.body(body)
			.footer(footer)
		);
	});
	return chart;
}
