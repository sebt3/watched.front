function wdP(ptext) {
	var text=ptext;
	function chart(s) { s.each(chart.init); return chart; }
	chart.init	= function() { 
		var root= d3.select(this).append('p').html(text);
		return chart;
	};
	return chart;
}
function wdBox() {
	var title, body, footer, cl="box-default", tools = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.title	= function(t) {title = t;return chart;};
	chart.tool	= function(t) {tools.push(t);return chart;};
	chart.class	= function(t) {cl = t;return chart;};
	chart.body	= function(t) {body = t;return chart;};
	chart.footer	= function(t) {footer = t;return chart;};
	chart.init	= function() { 
		var root= d3.select(this).append('div').attr('class', 'box '+cl), ttl;
		if (typeof title != 'undefined') {
			ttl = root.append('div').attr('class', 'box-header with-border');
			ttl.append('h3').attr('class', 'box-title').html(title);
		}
		if (tools.length>0 && typeof title != 'undefined') {
			ttl.append('div').attr('class', 'box-tools pull-right').selectAll('button').data(tools).enter().each(function(d,i) {
				if(typeof d == 'function') {
					d3.select(this).call(d);
					return;
				}
				var btn = d3.select(this).append('button').attr('type', 'button').attr('class','btn btn-box-tool');
				if (typeof d.action != 'undefined')
					btn.attr('data-widget', d.action)
				if (typeof d.icon != 'undefined')
					btn.append('i').attr('class', d.icon)
			});
		}
		var bod = root.append('div').attr('class', 'box-body');
		if (typeof body != 'undefined')
			bod.call(body)
		if (typeof footer != 'undefined')
			root.append('div').attr('class', 'box-footer').call(footer)
		return chart;
	};
	return chart;
}
function wdPanel() {
	var title, body, cl = "panel-default";
	function chart(s) { s.each(chart.init); return chart; }
	chart.title	= function(t) {title = t;return chart;};
	chart.class	= function(t) {cl = t;return chart;};
	chart.body	= function(t) {body = t;return chart;};
	chart.init	= function() { 
		var root= d3.select(this).append('div').attr('class', 'panel '+cl),
		    ttl	= root.append('div').attr('class', 'panel-heading').html(title),
		    bod = root.append('div').attr('class', 'panel-body');
		if (typeof body != 'undefined')
			bod.call(body)
		return chart;
	};
	return chart;
}
function wdModal() {
	var id, title, body, footer, cl = "modal-default";
	function chart(s) { s.each(chart.init); return chart; }
	chart.title	= function(t) {title = t;return chart;};
	chart.id	= function(t) {id = t;return chart;};
	chart.class	= function(t) {cl = t;return chart;};
	chart.body	= function(t) {body = t;return chart;};
	chart.footer	= function(t) {footer = t;return chart;};
	chart.init	= function() { 
		var root= d3.select(this).append('div').attr('class', 'modal fade '+cl).attr('id', id).attr('tabindex','-1').attr('role','dialog').append('div').attr('class','modal-dialog').attr('role','document').append('div').attr('class','modal-content')
		    ttl	= root.append('div').attr('class', 'modal-header'),
		    bod = root.append('div').attr('class', 'modal-body');
		ttl.append('button').attr('type', 'button').attr('class', 'close').attr('data-dismiss','modal').attr('aria-label','Close').append('span').attr('aria-hidden','true').html('&times;');
		if (typeof title != 'undefined')
			ttl.append('h4').attr('class','modal-title').html(title)
		if (typeof body != 'undefined')
			bod.call(body)
		if (typeof footer != 'undefined')
			root.append('div').attr('class','modal-footer').call(footer)
		return chart;
	};
	return chart;
}
function wdRow() {
	var cells = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.cell	= function(l,c) {cells.push({ 'obj': c, 'class':l});return chart;}
	chart.init	= function() { 
		d3.select(this).append('div').attr('class', 'row').selectAll('div').data(cells).enter().append('div').each(function(d,i) {
			var c = d3.select(this);
			if(typeof d.class != 'undefined')
				c.attr('class', d.class)
			if(typeof d.obj != 'undefined')
				c.call(d.obj)
		});
		return chart;
	};
	return chart;
}
function wdUnion() {
	var cells = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.item	= function(c) {cells.push(c);return chart;}
	chart.init	= function() { 
		var r = d3.select(this)
		cells.forEach(function(d) {r.append('span').call(d);});
		return chart;
	};
	return chart;
}
function wdPill() {
	var pills = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.data	= function(t) {pills = t;return chart;}
	chart.add	= function(t) {pills.push(t);return chart;}
	chart.init	= function() {
		/*if (d3.select(this).classed('box-footer'))
			d3.select(this).classed('no-padding',true);*/
		d3.select(this).append('ul').attr('class', 'nav nav-pills nav-stacked').selectAll('li').data(pills).enter().append('li').each(function(d,i) {
			var p = d3.select(this);
			if (typeof d.url != 'undefined')
				p = p.append('a').attr('href',d.url)
			if (typeof d.left != 'undefined')
				p.append('span').html(d.left)
			if (typeof d.right != 'undefined')
				p.append('span').attr('class','pull-right').html(d.right)
			if (typeof d.color != 'undefined')
				p.attr('class', d.color)
		});
		return chart;
	};
	return chart;
}
function wdList() {
	var items = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.data	= function(t) {items = t;;return chart;}
	chart.add	= function(t) {items.push(t);return chart;}
	chart.init	= function() {
		d3.select(this).append('ul').attr('class', 'list-unstyled').selectAll('li').data(items).enter().append('li').each(function(d,i) {
			if(typeof d == 'function') {
				d3.select(this).call(d);return
			}
			var p = d3.select(this);
			if (typeof d.url != 'undefined')
				p = p.append('a').attr('href',d.url)
			if (typeof d.color != 'undefined')
				p.attr('class', d.color)
			p.html(d.text)
		});
		return chart;
	};
	return chart;
}
function wdDesc() {
	var items = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.item	= function(t,d) { items.push({ 'left': t, 'right':d});return chart;}
	chart.init	= function() {
		var root = d3.select(this).append('dl').attr('class', 'dl-horizontal').selectAll().data(items).enter().each(function(d,i) {
			var t = d3.select(this)
			t.append('dt').html(d.left)
			t.append('dd').html(d.right)
		});
	}
	return chart;
}
function wdDescTable() {
	var items = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.data	= function(t) { items = t;return chart;}
	chart.item	= function(t,d,u) { items.push({ left: t, right:d, url: u});return chart;}
	chart.init	= function() {
		var root = d3.select(this).append('table').attr('class', 'table table-condensed table-striped table-hover').append('tbody').selectAll().data(items).enter().each(function(d,i) {
			var t = d3.select(this).append('tr')
			t.append('th').html(d.left)
			var x = t.append('td').attr('class','text-right')
			if (typeof d.url != 'undefined' && d.url != null)
				x = x.append('a').attr('href', d.url)
			x.html(d.right)
		});
	}
	return chart;
}
function wdProgess() {
	var items = [], title, url;
	function chart(s) { s.each(chart.init); return chart; }
	chart.title	= function(t) { title = t;return chart;}
	chart.url	= function(t) {   url = t;return chart;}
	chart.data	= function(t) { items = t;log(items);return chart;}
	chart.item	= function(p,c) { if (typeof c == 'undefined') c='progress-bar-success';items.push({ 'pct': p, 'class':c});return chart;}
	chart.init	= function() {
		var root = d3.select(this)
		if (typeof url != 'undefined')
			root  = root.append('a').attr('href', url)
		if (typeof title != 'undefined') {
			var t = root.append('div').attr('class', 'clearfix'), total=0
			items.forEach(function(i){total+=i.pct})
			t.append('span').attr('class','pull-left').html(title)
			t.append('small').attr('class','pull-right').text(Math.round(total)+'%')
		}
		root.append('div').attr('class', 'progress xs').selectAll().data(items).enter().each(function(d,i) {
			var t = d3.select(this).append('div').attr('style', 'width: '+d.pct+'%').attr('class', 'progress-bar '+d.class)
		});
	}
	return chart;
}
function wdForm() {
	var body, url = "#";
	function chart(s) { s.each(chart.init); return chart; }
	chart.body	= function(t) { body = t;return chart;}
	chart.url	= function(t) {  url = t;return chart;}
	chart.init	= function() {
		var f = d3.select(this).append('form').attr('class', 'form-horizontal').attr('action',url).attr('method','post')
		if (typeof body != 'undefined')
			f.call(body)
	}
	return chart;
}
function wdFormSelect(p_id) {
	var id = p_id, opts = [], val, i = 0;
	function chart(s) { s.each(chart.init); return chart; }
	chart.value	= function(t) { val = t;return chart;}
	chart.add	= function(t,n) { if (typeof n == 'undefined') n=i++;opts.push({ 'text': t, 'val':n});return chart;}
	chart.init	= function() {
		var s = d3.select(this).append('select').attr('class', 'form-control').attr('name', id)
		s.selectAll().data(opts).enter().each(function(d) {
			var o = d3.select(this).append('option').attr('value',d.val).html(d.text)
			if (val == d.val)
				o.attr('selected','selected')
		});
	}
	return chart;
}
function wdFormGroup(p_id) {
	var label = "", addons = [], id = p_id, val, place, obj, type='text';
	function chart(s) { s.each(chart.init); return chart; }
	chart.label	= function(t) {   label = t;return chart;}
	chart.obj	= function(t) {   obj   = t;return chart;}
	chart.type	= function(t) {   type  = t;return chart;}
	chart.value	= function(t,h) { val   = t; if (typeof h != 'undefined') place=h;return chart;}
	chart.add	= function(t,c) { if (typeof c == 'undefined') c=false;addons.push({ 'text': t, 'before':c});return chart;}
	chart.init	= function() {
		var g	= d3.select(this).append('div').attr('class', 'form-group')
		g.append('label').attr('for',id).attr('class','col-sm-2 control-label').html(label)
		var d	= g.append('div').attr('class', 'col-sm-10')
		if (addons.length>0)
			d = d.append('div').attr('class', 'input-group')
		addons.forEach(function(i){
			if(i.before)
				d.append('span').attr('class','input-group-addon').html(i.text)
		});
		if(typeof obj != 'undefined')
			d.call(obj)
		else {
			var t = d.append('input').attr('class','form-control').attr('id',id).attr('name',id).attr('type',type)
			if(typeof place != 'undefined' && type!='hidden')
				t.attr('placeholder', place)
			else if(type=='hidden')
				d.append('div').attr('class','form-control').html(place)
			if(typeof val != 'undefined')
				t.attr('value', val)
		}
		addons.forEach(function(i){
			if(!i.before)
				d.append('span').attr('class','input-group-addon').html(i.text)
		});
	}
	return chart;
}
function wdButtonGroup() {
	var lefts = [], rights = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.left	= function(t) { lefts.push(t);  return chart; }
	chart.right	= function(t) { rights.push(t); return chart; }
	chart.init	= function() {
		var l = d3.select(this);
		lefts.forEach(function(d) {l.call(d).append('span').html('&nbsp;'); });
		if (rights.length==0) return;
		r = l.append('div').attr('class', 'pull-right');
		rights.forEach(function(d) {r.call(d).append('span').html('&nbsp;'); });
	}
	return chart;
}
function wdAButton() {
	var cl = "btn-default", url = "#", icon, text="";
	function chart(s) { s.each(chart.init); return chart; }
	chart.url	= function(t) { url = t;return chart;}
	chart.class	= function(t) {  cl = t;return chart;};
	chart.icon	= function(t) {icon = t;return chart;};
	chart.text	= function(t) {text = t;return chart;};
	chart.init	= function() {
		var r = d3.select(this).append('a').attr('href',url).attr('class', 'btn '+cl)
		if(typeof icon != 'undefined')
			r.append('i').attr('aria-hidden','true').attr('class',icon)
		r.append('span').text(' '+text)
	}
	return chart;
}
function wdDropButton() {
	var cl = "btn-default", icon = "fa fa-caret-down", text="", items  = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.url	= function(t) { url = t;return chart;}
	chart.class	= function(t) {  cl = t;return chart;};
	chart.item	= function(t,c) { items.push({ 'text': t, 'call':c});return chart;}
	chart.icon	= function(t) {icon = t;return chart;};
	chart.text	= function(t) {text = t;return chart;};
	chart.init	= function() {
		var div = d3.select(this).append('div').attr('class','btn-group'),
		    b = div.append('button').attr('class', 'btn '+cl+' dropdown-toggle').attr('data-toggle','dropdown'),
		    u = div.append('ul').attr('class','dropdown-menu');
		b.append('span').html(text+' ')
		b.append('i').attr('aria-hidden','true').attr('class',icon)
		u.selectAll('li').data(items).enter().each(function(d,i) {
			d3.select(this).append('li').append('a').attr('onclick',d.call).html(d.text)
		});
	}
	return chart;
}
function wdButton() {
	var cl = "btn-default", url = "#", icon, text="", type="submit";
	function chart(s) { s.each(chart.init); return chart; }
	chart.url	= function(t) { url = t;return chart;}
	chart.class	= function(t) {  cl = t;return chart;};
	chart.type	= function(t) {type = t;return chart;};
	chart.icon	= function(t) {icon = t;return chart;};
	chart.text	= function(t) {text = t;return chart;};
	chart.init	= function() {
		var r = d3.select(this).append('button').attr('class', 'btn '+cl)
		if(typeof icon != 'undefined')
			r.append('i').attr('aria-hidden','true').attr('class',icon)
		r.append('span').html(' '+text)
	}
	return chart;
}
function wdModalSubmit() {
	var cl = "btn-outline", url = "#", icon, text="";
	function chart(s) { s.each(chart.init); return chart; }
	chart.url	= function(t) { url = t;return chart;}
	chart.class	= function(t) {  cl = t;return chart;};
	chart.icon	= function(t) {icon = t;return chart;};
	chart.text	= function(t) {text = t;return chart;};
	chart.init	= function() {
		var r = d3.select(this).append('form').attr('action',url).attr('method','post') .append('button').attr('class', 'btn '+cl)
		if(typeof icon != 'undefined')
			r.append('i').attr('aria-hidden','true').attr('class',icon)
		r.append('span').html(' '+text)
	}
	return chart;
}
function wdModalDelete() {
	var cl = "btn-outline", url = "#", icon, text="", type="submit";
	function chart(s) { s.each(chart.init); return chart; }
	chart.url	= function(t) { url = t;return chart;}
	chart.title	= function(t) {title = t;return chart;};
	chart.id	= function(t) {id = t;return chart;};
	chart.text	= function(t) {text = t;return chart;};
	chart.body	= function(t) {body = t;return chart;};
	chart.init	= function() {
		var r = d3.select(this).call(wdModal().class('modal-warning').id(id) .title(title).body(body).footer(wdModalSubmit().url(url).text(text)))
	}
	return chart;
}