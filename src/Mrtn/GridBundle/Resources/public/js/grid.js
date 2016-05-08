$(function() {
	var params    = {};
	var container = $('#zgrid_container');
	var gridUrl   = container.attr('data-url');
	var multiple  = false;
	
	// Code 17: Ctrl key
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
		var matches = src.match(/^([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\(([a-zA-Z0-9_]+):([^\)]+)\)$/);
		
		if (matches === null) {
			throw 'Invalid definition: "' + src + '"';
		}
		
		return {
			param:  matches[1],
			action: matches[2],
			field:  matches[3],
			value:  matches[4]
		};
	};
	
	/**
	 * Process value
	 * 
	 * @param   {String} value
	 * @returns {String}
	 */
	var processValue = function(value)
	{
		var matches = value.match(/^\[(.+?)\]$/);
		
		if (matches === null) {
			return value;
		}
		
		return $(matches[1]).val();
	};
	
	/**
	 * Process action (apply to parameters)
	 * 
	 * @param   {Object} params
	 * @param   {Object} action
	 */
	var processAction = function(params, action)
	{
		// Replace parameter by value
		if (action.action === 'replace') {
			if (multiple) {
				if (typeof params[action.param] === 'undefined') {
					params[action.param] = {};
				}
			} else {
				params[action.param] = {};
			}
			
			params[action.param][action.field] = action.value;
			return;
		}
	
		// Add value to parameter
		if (action.action === 'set') {
			if (typeof params[action.param] === 'undefined') {
				params[action.param] = {};
			}
			
			params[action.param][action.field] = action.value;
			return;
		}
		
		// Remove value from parameter
		if (action.action === 'remove') {
			if (typeof params[action.param] === 'undefined') {
				return;
			}
			
			delete params[action.param][action.field];
			
			if ($.isEmptyObject(params[action.param])) {
				delete params[action.param];
			}
		}
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
		
		for (var paramKey in params) {
			var param = params[paramKey];
			var fieldList = [];
			
			for (var fieldKey in param) {
				fieldList.push(fieldKey + ':' + param[fieldKey]);
			}
			
			paramsList.push(paramKey + '=' + fieldList.join(','));
		}
		
		return paramsList.join('&');
	};
	
	/**
	 * Handle parameter's action
	 */
	var parametersHandler = function()
	{
		var definition = $(this).attr('data-definition');
		var action     = parseDefinition(definition);
		
		action.value = processValue(action.value);
		
		if (action.value === '') {
			return;
		}
		
		processAction(params, action);
		
		$.ajax({
			url: gridUrl + '?' + assembleRequest(params),
			success: function(response) {
				container.html(response);
				container.find('.js-zgrid-param').click(parametersHandler);
			}
		});
	};
	
	$('.js-zgrid-param').click(parametersHandler);
});