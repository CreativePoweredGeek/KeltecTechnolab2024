/**
 * URL functions
 */
export function getUrlValue(searchVariable, fallbackValue, url) {

	var searchString = ! _.isUndefined(url) ? url.split('?')[1] : window.location.search.substring(1);
	var arrayOfVariables = searchString.split('&');

	for (var i = 0; i < arrayOfVariables.length; i++)
	{
		var keyValuePair = arrayOfVariables[i].split('=');
		if(keyValuePair[0] == searchVariable)
		{
			return keyValuePair[1];
		}
	}
	if(! _.isUndefined(fallbackValue))
	{
		return fallbackValue;
	}

	return false;
}

export function buildUrl(string) {
	return EE.BASE.split("?")[0] + '?' + string + '&S=' + getUrlValue("S", 0, EE.BASE);
}

/**
 * Get a fresh CSRF Token every time this is called.
 * When there is a dispatch or commit to update the csrf_token store state,
 * this function gets called.
 * @returns {*}
 */
export function handleToken() {
	let token = EE.CSRF_TOKEN;
	window.token = token;
	return token;
}