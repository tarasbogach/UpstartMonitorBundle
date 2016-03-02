class @UpstartMonitor
	ns: null
	el: null
	cnf: null
	job: null
	tag: null
	ws: null
	filter: null
	constructor:(el, @cnf)->
		@ns = arguments.callee.name
		@el = {}
		@addEl('root', el)
		@initEl()
		@job = {}
		@tag = {}
		@filter = []
		@createTag(tag) for tagName, tag of @cnf.tag
		@createJob(job) for jobName, job of @cnf.job
		@el.start.click({action: 'start'}, @onAction)
		@el.stop.click({action: 'stop'}, @onAction)
		@el.restart.click({action: 'restart'}, @onAction)
		@createWs()
		console?.log(@)
	onAction:(ev)=>
		@ws.send(JSON.stringify({
			type:'action'
			data: {
				action: ev.data.action
				filter: ev.data.filer ? @filter
			}
		}))
	createWs:=>
		@ws?.close()
		@ws = new WebSocket(@cnf.client.schema+'://'+window.location.hostname+':'+@cnf.client.port+@cnf.client.path)
		@ws.onmessage = @onMessage
		@ws.onopen = @onConnected
		@ws.onclose = @onDisconnected
		@ws.onerror = @onError
	onMessage:(e)=>
		msg = JSON.parse(e.data)
		switch msg.type
			when 'state'
				@updateState(msg.data)
	updateState:(jobs)->
		for name, state of jobs
			cnf = @cnf.job[name]
			els = @job[name]
			quantity = if cnf.quantity > 1 then state[1] else state[0]
			prevQuantity = els.quantity ? 0
			els.quantity = quantity
			els.started.text(quantity)
			els.stopped.text(cnf.quantity - quantity)
			if prevQuantity != quantity
				highlight = @getNsClass('highlight')
				els.row.removeClass(highlight).addClass(highlight)
	onConnected:(e)=>
		@el.disconnected.hide()
	onDisconnected:(e)=>
		@el.disconnected.show()
		@createWs()
	onError:(e)=>
		@el.disconnected.show()
	createTag:(tag)->
		@tag[tag.name] = $('<button class="navbar-btn btn btn-xs btn-success"></button>')
			.data(tag)
			.text(tag.name ? '')
			.appendTo(@el.tag)
		@el.tag.append(' ')
	createJob:(job)->
		@job[job.name] = map = {
			quantity: 0
			row: null
			name: null
			started: null
			stopped: null
			start: null
			stop: null
			restart: null
			log: null
			tags: null
		}
		td = '<td></td>'
		map.row = $('<tr></tr>').appendTo(@el.job)
		map.name = $('<strong></strong>').text(job.name ? '').appendTo($(td).attr('width', '80%').appendTo(map.row))
		map.tags = $(td).appendTo(map.row)
		for tagName in job.tag
			tagEl = $('<button class="btn btn-xs btn-success"></button>')
				.data(@cnf.tag[tagName])
				.text(tagName ? '')
				.appendTo(map.tags)
			@tag[tagName].add(tagEl)
			map.tags.append(' ')
		map.started = $('<span class="label label-success">0</span>').appendTo($(td).appendTo(map.row))
		map.stopped = $('<span class="label label-danger">0</span>').appendTo($(td).appendTo(map.row))
		map.start = $('
			<button class="btn btn-xs btn-success" title="Start">
				<span class="glyphicon glyphicon-play"></span>
			</button>
		').prop('disabled', true).appendTo($(td).appendTo(map.row))
		map.stop = $('
			<button class="btn btn-xs btn-danger" title="Stop">
				<span class="glyphicon glyphicon-stop"></span>
			</button>
		').prop('disabled', true).appendTo($(td).appendTo(map.row))
		map.restart = $('
			<button class="btn btn-xs btn-warning" title="Restart">
				<span class="glyphicon glyphicon-refresh"></span>
			</button>
		').prop('disabled', true).appendTo($(td).appendTo(map.row))
		map.log = $('
			<button class="btn btn-xs btn-info" title="Log">
				<span class="glyphicon glyphicon-open-file"></span>
			</button>
		').appendTo($(td).appendTo(map.row))
	addEl: (name, tmpl = '<div></div>') -> @el[name] = $(tmpl).addClass(@getElClass(name))
	getElClass: (name) -> @ns + '-el-' + name
	getNsClass: (name) -> @ns + '-' + name
	getNsSelector: (name) -> '.' + @ns + '-' + name
	initEl: ->
		cut = @getElClass('')
		pattern = new RegExp(cut + '[a-zA-Z0-9_-]+', 'g')
		els = @el.root.find('*')
		for el in els
			el = $(el)
			classes = el.attr('class')?.match(pattern)
			@addEl(_class.substring(cut.length), el) for _class in classes if classes?


