(function($) {
	/**
	 * Multiple action state
	 * 
	 * @type Boolean
	 */
	var multiple  = false;

	/**
	 * Multiple actions detector
	 * Code 17: Ctrl key
	 */
	$(document).keydown(function(event) {
		if (event.which === 17) {
			multiple = true;
		}
	}).keyup(function(event) {
		if (event.which === 17) {
			multiple = false;
		}
	});

	/**
	 * Parse action's definition
	 * 
	 * {parameter}.{action}({field}:{args})
	 * 
	 * Example:
	 * order.set(name:asc)
	 * 
	 * @param   {String} src
	 * @returns {Object}
	 */
	var parseDefinition = function(src)
	{
		// parameter.action(args)
		var matches = src.match(/^([a-zA-Z0-9_\[\]]+)\.([a-zA-Z0-9_]+)\((.*)\)$/);

		if (matches === null) {
			throw 'Invalid definition: "' + src + '"';
		}
		
		// Detect array: parameter[offset]
		var arrayMatches = matches[1].match(/^([a-zA-Z0-9_]+)\[([a-zA-Z0-9_]+)\]$/);
		
		if (arrayMatches === null) {
			return {
				param:  matches[1],
				action: matches[2],
				args:   matches[3]
			};
		}
		
		return {
			param:  arrayMatches[1],
			offset: arrayMatches[2],
			action: matches[2],
			args:   matches[3]
		};
	};
	
	/**
	 * Process reference
	 * 
	 * @param   {String} value
	 * @returns {String}
	 */
	var processReference = function(value)
	{
		var matches = value.match(/^\[(.+?)\]$/);

		if (matches === null) {
			return value;
		}

		return $(matches[1]).val();
	};
	
	/**
	 * Assemble request string from parameters set
	 * 
	 * @param   {Object} params
	 * @returns {String}
	 */
	var assembleRequest = function(params)
	{
		var paramsList = [];

		for (var key in params) {
			var param = params[key];
			
			if (typeof param === 'object') {
				for (var property in param) {
					var value = param[property];
					
					paramsList.push(key + '[' + property + ']=' + value);
				}
				
				continue;
			}
			
			paramsList.push(key + '=' + param);
		}

		return paramsList.join('&');
	};

	/**
	 * Process action (apply to parameters)
	 * 
	 * @param {Object} params
	 * @param {Object} action
	 */
	var processAction = function(params, action)
	{
		// Replace parameter by value
		if (action.action === 'replace') {
			if (typeof action.offset === 'undefined') {
				throw '"replace()" can work only with arrays'
			}
			
			if (multiple) {
				if (typeof params[action.param] === 'undefined') {
					params[action.param] = {};
				}
			} else {
				params[action.param] = {};
			}

			params[action.param][action.offset] = processReference(action.args);
			return;
		}

		// Add value to parameter
		if (action.action === 'set') {
			if (typeof action.offset === 'undefined') {
				params[action.param] = processReference(action.args);
				return;
			}
			
			if (typeof params[action.param] === 'undefined') {
				params[action.param] = {};
			}
			
			params[action.param][action.offset] = processReference(action.args);
		}

		// Remove value from parameter
		if (action.action === 'remove') {
			if (typeof action.offset === 'undefined') {
				delete params[action.param];
				return;
			}
			
			delete params[action.param][action.offset];

			if ($.isEmptyObject(params[action.param])) {
				delete params[action.param];
			}
		}
	};

	/**
	 * Grid object
	 * 
	 * @returns {Grid}
	 */
	var Grid = function(container)
	{		
		this.dataParams = {};
		this.container  = container;
		this.dataUrl    = this.container.attr('data-url');
		
		var self = this;
		
		/**
		 * Handle parameter's action
		 */
		this.parametersHandler = function(event)
		{
			event.preventDefault();
			
			var definition = $(this).attr('data-definition');
			var action     = parseDefinition(definition);

			processAction(self.dataParams, action);
			
			$.ajax({
				url: self.dataUrl + '?' + assembleRequest(self.dataParams),
				success: function(response) {
					self.container.html(response);
					self.container.find('.js-zgrid-param').click(self.parametersHandler);
				}
			});
		};
		
		this.container.find('.js-zgrid-param').click(this.parametersHandler);
	};

	/**
	 * jQuery plugin init
	 * 
	 * @returns {Grid}
	 */
	$.fn.zgrid = function()
	{
		this.each(function() {
			new Grid($(this));
		});
		
		return this;
	};
})(jQuery);