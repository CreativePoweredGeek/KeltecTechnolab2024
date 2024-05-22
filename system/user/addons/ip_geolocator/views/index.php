<div class="col-group">
    <div class="col w-16">
        <div class="box">
            <h1>IP Geo Locator</h1>
            <div class="md-wrap">

                <h1>Introduction</h1>
                <p>The IP Geo Locator uses GeoLite data created by MaxMind, available from <a href="http://www.maxmind.com" target="_blank">http://www.maxmind.com</a> and uses the geoPlugin service at <a href="https://www.geoplugin.com/" target="_blank">www.geoplugin.com</a> where you can find detailed information about the service.</p>

                <p>Before using this add-on, please read the geoPlugin <a href="https://www.geoplugin.com/privacy" target="_blank">privacy policy and terms of service</a> carefully and also take careful note of the <a href="#terms">Terms and Conditions</a> at the bottom of this page.</p>

                <p>We recommend you register with geoPlugin to use this add-on: <a href="https://www.geoplugin.com/signup" target="_blank">www.geoplugin.com/signup</a></p>

                <p>This add-on attempts to identify your site's visitors by using the IP address returned by PHP's <code>$_SERVER['REMOTE_ADDR']</code> variable.</p>

                <p>NB: Results of the first API call for location data are cached so that subsequent calls to any of the tags on the same page will use the cached results.</p>

                <h1>Tags and Parameters</h1>

                <p>This add-on provides the following tags:</p>
                <ul>
                    <li><code>{exp:ip_geolocator:country_code}</code></li>
                    <li><code>{exp:ip_geolocator:continent_code}</code></li>
                    <li><code>{exp:ip_geolocator:ip_lookup}</code></li>
                    <li><code>{exp:ip_geolocator:get_variable}</code></li>
                    <li><code>{exp:ip_geolocator:is_allowed}</code></li>
                    <li><code>{exp:ip_geolocator:geo_data} ... {/exp:ip_geolocator:geo_data}</code></li>
                    <li><code>{exp:ip_geolocator:near_to_user} ... {/exp:ip_geolocator:near_to_user}</code></li>
                    <li><code>{exp:ip_geolocator:near_to_lat_lng} ... {/exp:ip_geolocator:near_to_lat_lng}</code></li>
                </ul>

                <p>You can use the following common parameters in any of the above tags:</p>

                <p><code>currency="GBP"</code> The default base currency. If omitted it will default to "GBP".</p>

                <p><code>language="en"</code> The default language. If omitted will default to "en"</p>

                <p><code>error_action="silent|no_results|fatal_error"</code> Defines how errors should be handled by the add-on. Use one option only: silent = fail silently, no_results = output EE no_results (tag pairs only) and fatal_error = stop the template and display an error.</p>

                <p><code>detect_bots="yes|no"</code> ( default is "yes") Should the plugin detect bots? If so you can choose the behaviour by specifying <code>bot_action</code> below.</p>

                <p><code>bot_action="silent|error"</code> ( default is "silent") If a bot is detected either continue silently or issue a fatal error.</p>
                
                <p><code>bot_error_message="No Bots allowed"</code> If <code>bot_action</code> is set to "error" then display this message as an EE fatal error. The default message is "No Bots allowed"</p>

                <h1>Country Code</h1>
                <p>Returns the visitor's country code</p>
                <p>{exp:ip_geolocator:country_code}</p>
                <p><b>Parameters</b></p>
                <p><code>default="GB"</code> Optional: specify a default country code which will be used if geo location fails. If omitted this will default to "GB".</p>

                <h1>Continent Code</h1>
                <p>Returns the visitor's continent code</p>
                <p>{exp:ip_geolocator:continent_code}</p>
                <p><b>Parameters</b></p>
                <p><code>default="EU"</code> Optional: specify a default continent code which will be used if geo location fails. If omitted this will default to "EU".</p>

                <h1>IP Lookup</h1>
                <p>Looks up a specific IP address</p>
                <p>{exp:ip_geolocator:ip_lookup}</p>
                <p><b>Parameters</b></p>
                <p><code>ip="216.58.201.4"</code> - Required: the IP address to lookup. The result is the same as using the <a href="#geo_data">Geo Data</a> tag, see below</p>

                <h1>Specific variables</h1>
                <p>To display any of the variables returned by the API, use this tag.</p>
                <code>{exp:ip_geolocator:get_variable name="region"}</code>
                <p><b>Parameters</b></p>
                <p><code>default_country="FR"</code> Optional, specify a default country if geo location fails. If omitted this will default to to "GB".</p>
                <p><code>default_continent="EU"</code> Optional, specify a default continent code if geo location fails. If omitted this will default to to "EU".</p>

                <p>The <code>name</code> parameter supports the following variables:</p>

                <ul>
                    <li>ip</li>
                    <li>city</li>
                    <li>region</li>
                    <li>region_code</li>
                    <li>region_name</li>
                    <li>dma_code</li>
                    <li>country_code</li>
                    <li>country_name</li>
                    <li>in_eu</li>
                    <li>eu_vat_rate</li>
                    <li>continent_code</li>
                    <li>continent_name</li>
                    <li>latitude</li>
                    <li>longitude</li>
                    <li>location_accuracy_radius</li>
                    <li>timezone</li>
                    <li>currency_code</li>
                    <li>currency_symbol</li>
                    <li>currency_converter</li>
                </ul>

                <h1>Is Allowed?</h1>
                <p>Check to see if a user's country code is on a supplied list of allowed codes. Returns TRUE or FALSE for use in an EE {if} statement</p>
                <code>{exp:ip_geolocator:is_allowed allowed="XX|XX|XX|XX"}</code>
                <p><b>Parameters</b></p>
                <p><code>allowed="XX|XX|XX|XX"</code> Checks to see if the user's country code is included in a supplied list of "allowed" country codes using the parameter allowed="XX|XX|XX". If the country code cannot be determined then the default country code is used either from the <code>default=""</code> parameter if it's supplied or the internal default set by this add-on.</p>                

                <h1 id="geo_data">GEO Data</h1>

                <p>All geo location data returned by the API call can be displayed using this tag pair.</p>
                <p><b>Parameters</b></p>
                <p><code>default_country="FR"</code> Optional, specify a default country if geo location fails. If omitted this will default to to "GB".</p>
                <p><code>default_continent="EU"</code> Optional, specify a default continent code if geo location fails. If omitted this will default to to "EU".</p>

    <pre><code>
    {exp:ip_geolocator:geo_data}
        IP: {ip}
        City: {city}
        Region: {region}
        Region Code: {region_code}
        Region Name: {region_name}
        DMA Code: {dma_code}
        Country Code: {country_code}
        Country Name: {country_name}
        In EU? {in_eu}
        EU VAT Rate: {eu_vat_rate}%
        Continent Code: {continent_code}
        Continent Name: {continent_name}
        Lat: {latitude}
        Lng: {longitude}
        Location Accuracy Radius: {location_accuracy_radius}
        Time Zone: {timezone}
        Currency Code: {currency_code}
        Currency Symbol: {currency_symbol}
        Currency Converter: {currency_converter}
    {/exp:ip_geolocator:geo_data}
</code></pre>

                <h1>Nearby</h1>
                <p>Nearby locations to the visitor can be retrieved using this tag pair.</p>
                <p><b>Parameters</b></p>
                <p><code>limit="5"</code> Limit the number of nearby results. If omitted this will default to 10.</p>
                <p><code>radius="5"</code> Limit the radius nearby results in miles. If omitted this will default to 10.</p>
<pre><code>
{exp:ip_geolocator:near_to_user limit="5" radius="20"}
    Place: {place}
    Country Code: {country_code}
    Region: {region}
    Lat: {latitude}
    Lng: {longitude}
    Distance (m): {distance_miles}
    Distance (km): {distance_kilometers}
{/exp:ip_geolocator:near_to_user}
</code></pre>

                <h1>Near to Specific Latitude and Longitude</h1>
                <p>This tag pair enables you to feed in specifc longitude and latitude values to perform a "nearby" search.</p>
                <p><code>limit="5"</code> Limit the number of nearby results. If omitted this will default to 10.</p>
                <p><code>radius="5"</code> Limit the radius nearby results in miles. If omitted this will default to 10.</p>
                <p><code>lat="xxxx"</code> Required: the latitude value</p>
                <p><code>lng="xxx"</code> Required: the longitude value.</p>
                <p>NB: The code example below also shows the use of the <code>error_action</code> parameter</p>
<pre><code>
{exp:ip_geolocator:near_to_lat_lng limit="5" radius="20" lat="51.4802600" lng="-0.1993000" error_action="no_results"}
    Place: {place}
    Country Code: {country_code}
    Region: {region}
    Lat: {latitude}
    Lng: {longitude}
    Distance (m): {distance_miles}
    Distance (km): {distance_kilometers}

    {if no_results}
        Could not find any results
    {/if}

{/exp:ip_geolocator:near_to_lat_lng}
</code></pre>

                <h1 id="terms">Terms and Conditions</h1>

                <p>This add-on was created by <a href="https://www.climbingturn.co.uk/" target="_blank">Climbing Turn Ltd</a> and is provided for free. <b>Use of this add-on is at your own risk</b>. By using this add-on you agree that Climbing Turn Ltd cannot provide any guarantees as to the accuracy of the data provided, the reliability of the add-on or be liable for any loss or damage resulting from the use of this add-on under any circumstances. By using this add-on you are also bound by the terms of the geoPlugin service which can be read here <a href="https://www.geoplugin.com/privacy" target="_blank">www.geoplugin.com/privacy</a>.</p>

                <p>If you do not agree to the above terms, please uninstall the add-on.</p>

                <p>We'd also love to hear from you if you have any comments or suggestion relating to this software. Please do get in touch using our <a href="https://www.climbingturn.co.uk/contact" target="_blank">Contact Form</a></p>


            </div>
        </div>
    </div>
</div>