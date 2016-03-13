class @UpstartMonitor
	ns: null
	el: null
	cnf: null
	job: null
	tag: null
	ws: null
	filterTag: null
	constructor:(el, @cnf)->
		@ns = arguments.callee.name
		@el = {}
		@addEl('root', el)
		@initEl()
		@job = {}
		@tag = {}
		@createTag(tag) for tagName, tag of @cnf.tag
		@createJob(job) for jobName, job of @cnf.job
		@el.allTags.click(@onTag)
		@el.start.click({action: 'start'}, @onAction)
		@el.stop.click({action: 'stop'}, @onAction)
		@el.restart.click({action: 'restart'}, @onAction)
		@createWs()
	onAction:(ev)=>
		@ws.send(JSON.stringify({
			type:'action'
			data: {
				action: ev.data.action
				job: ev.data.job?.name ? null
				tag: @filterTag ? null
			}
		}))
	createWs:=>
		@ws?.close()
		@ws = new WebSocket(@cnf.client.schema+'://'+window.location.hostname+':'+@cnf.client.port+@cnf.client.path+'?accessToken='+@cnf.accessToken)
		@ws.onmessage = @onMessage
		@ws.onopen = @onConnected
		@ws.onclose = @onDisconnected
		@ws.onerror = @onError
	onMessage:(e)=>
		msg = JSON.parse(e.data)
		switch msg.type
			when 'state'
				@updateState(msg.data)
			when 'accessDeny'
				window.location.reload() if true == confirm("Your access token seems to be expired.\nDo reload this page to fix the problem?")
			else
				console?.log(msg)
	updateState:(jobs)->
		highlight = @getNsClass('highlight')
		for name, state of jobs
			cnf = @cnf.job[name]
			els = @job[name]
			quantity = if cnf.quantity > 1 then state[1] else state[0]
			els.started.text(quantity)
			switch true
				when quantity == 0 then cssState = 'label-danger'
				when quantity < cnf.quantity then cssState = 'label-warning'
				when quantity >= cnf.quantity then cssState = 'label-success'
			els.state
				.removeClass('label-danger')
				.removeClass('label-success')
				.removeClass('label-warning')
				.addClass(cssState)
			els.stop.prop('disabled', quantity == 0)
			els.restart.prop('disabled', quantity == 0)
			els.start.prop('disabled', quantity >= cnf.quantity)
			if els.quantity != quantity
				console?.log(1)
				els.row.removeClass(highlight)
				do (els)->
					setTimeout(
						-> els.row.addClass(highlight),
						10
					)
			els.quantity = quantity
	onConnected:(e)=>
		@el.disconnected.hide()
	onDisconnected:(e)=>
		@el.disconnected.show()
		@createWs()
	onError:(e)=>
		@el.disconnected.show()
	createTag:(tag)->
		$('<li><a href="#"></a></li>')
			.appendTo(@el.tag)
			.click({tag: tag.name}, @onTag)
			.find('a')
			.text(tag.name)
		@tag[tag.name] = $([]) if tag?
	onTag:(ev)=>
		if ev.data?.tag?
			@el.job.find('tr').hide()
			@tag[ev.data.tag].show()
			@filterTag = ev.data.tag
		else
			@el.job.find('tr').show()
			@filterTag = null
	createJob:(job)->
		@job[job.name] = map = {
			quantity: 0
			row: null
			name: null
			started: null
			state: null
			start: null
			stop: null
			restart: null
			log: null
			tags: null
		}
		td = '<td></td>'
		map.row = $('<tr></tr>').data('name', job.name).appendTo(@el.job)
		map.state = $('<span class="label label-danger"></span>').appendTo($(td).appendTo(map.row))
		map.started = $('<span>0</span>').appendTo(map.state)
		map.state.append(' / ')
		$('<span></span>').text(job.quantity).appendTo(map.state)
		nameTd = $(td).css('width', '80%').appendTo(map.row)
		map.name = $('<strong></strong>')
			.text(job.name ? '')
			.appendTo(nameTd)
		map.tags = $('<span class="pull-right"></span>').appendTo(nameTd)
		map.tags.append(' ')
		for tagName in job.tag
			$('<button class="btn btn-xs btn-primary"></button>')
				.appendTo(map.tags)
				.click({tag: tagName}, @onTag)
				.text(tagName ? '')
			@tag[tagName] = @tag[tagName].add(map.row)
			map.tags.append(' ')
		map.start = $('
			<button class="btn btn-xs btn-success" title="Start">
				<span class="glyphicon glyphicon-play"></span>
			</button>
		')
			.prop('disabled', true)
			.click({action: 'start', job: job}, @onAction)
			.appendTo($(td).appendTo(map.row))
		map.stop = $('
			<button class="btn btn-xs btn-danger" title="Stop">
				<span class="glyphicon glyphicon-stop"></span>
			</button>
		')
			.prop('disabled', true)
			.click({action: 'stop', job: job}, @onAction)
			.appendTo($(td).appendTo(map.row))
		map.restart = $('
			<button class="btn btn-xs btn-warning" title="Restart">
				<span class="glyphicon glyphicon-refresh"></span>
			</button>
		')
			.prop('disabled', true)
			.click({action: 'restart', job: job}, @onAction)
			.appendTo($(td).appendTo(map.row))
		map.log = $('
			<button class="btn btn-xs btn-info" title="Log">
				<span class="glyphicon glyphicon-open-file"></span>
			</button>
		')
			.click({action: 'log', job: job}, @onAction)
			.appendTo($(td).appendTo(map.row))
	addEl: (name, tmpl = '<div></div>') ->
		el = $(tmpl).addClass(@getElClass(name))
		@el[name] =  if @el[name]? then @el[name].add(el) else el
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


