# Hop Minifizer

Hop Minifizer takes your site’s CSS, Javascript and even SCSS files, and it combines them, compresses them, saves them as disk files locally (or even on Amazon S3), so that your site loads and displays far more quickly, but your development process is still modular and easy to manage. This add-on is based on Minimee, by John D. Wells, and adds additional functionality.

## System Requirements:
- PHP5+
- ExpressionEngine 3+

## Optional Requirements:
- EE2.4+ is required for HTML minification
- `cURL` or `file_get_contents()` is required if using `{stylesheet=}` or external URLs
- If using `file_get_contents()`, PHP must be compiled with support for fopen wrappers (`allow_url_fopen` must be set to `true` in `PHP.ini`)
- If using `file_get_contents()` and combining/minifying files over https, PHP must be compiled with OpenSSL support

# Installation

1. Copy folder `/system/user/addons/hop_minifizer` to your site's own system addons folder
2. Create a folder, __above webroot__, for Hop_minifizer to save your cached files to, e.g. `public_html/cache`
3. Install Hop_minifizer's extension, __or__ amend your `config.php` to configure the cache folder's location & url
4. If you wish to enable compression, copy the contents of `sample.htaccess` to an htaccess file placed in your cache folder, e.g. `public_html/cache/.htaccess`

## Features
- All settings can be configured via config or control panel, _as well as_ via tag parameters
- Ability to turn on and off minification and/or combining per asset type
- Better, more verbose debugging
- API for 3rd party add-ons
- Path & URL settings can now be relative to site
- Out-of-the-box default configuration means near zero setup
- Shorthand `exp:hop_minifizer` tag allows for quick caching, plus access to a powerful API "interface"
- Display your cache: in a tag, embedded inline, or just the URL
- Purpose-built compatibility with RequireJS, etc
- Disable or override the URLs which are prepended to `image` & `@import` paths in CSS
- Queue assets into a specific order with the `priority=""` parameter
- Assets are queue'd and sorted after all templates have been parsed (see the "Croxton Queue" below)
- Automatically delete expired caches with the `cleanup=""` setting
- Verbose template debugging messages to help easily track down errors
- Now works with Amazon Simple Storage Service (Amazon S3)
- Hooks for 3rd party integration

## Configuration

Preferences can be configured through the Hop Minifizer EE control panel, by tag parameters, or by editing EE's `system/user/config/config.php` file. The control panel interface adds simplicity and convenience, but at the cost of an extra database call.

To get the most out of Hop Minifizer's performance offering, configure via EE's $config variable:

```php
# example: configuring Hop Minifizer preferences in config.php

$config['hop_minifizer'] = array(
    'cache_path' => '/path/to/cache',
    'cache_url' => 'http://example.com/cache',
);
```

### Config via Control Panel
Each field of Hop Minifizer's control panel screen contains helpul hints and instructions.

### Turn on debug mode
The helper file will output curl errors to help identify the issues.

#### Basic Preferences
The first panel allows for basic configuration of Hop Minifizer, including whether to combine and/or minify each asset type.

If no values are supplied for Cache Path or Cache URL, Hop Minifizer will automatically look for a `cache` folder at your root.

![Hop Minifizer Control Panel screenshot -  Basic Preferences](/img/config-basic.png)

#### Advanced Preferences
For 90% of all setups, you will not need to edit the Advanced Preferences. If you do and encounter problems, these should be the first things you reset or change.

![Hop Minifizer Control Panel screenshot -  Advanced Preferences](/img/config-advanced.png)

#### Amazon S3 Preferences

If you are using Amazon's S3 storage service, this is where you'll configure your access keys and other settings.

![Hop Minifizer Control Panel screenshot -  Amazon S3 Preferences](/img/config-s3.png)

### Config via EE's $config object
Configuring Hop Minifizer via EE's `$config` has the advantage of not requiring any database calls to initialise or run, saving precious time on your page loads. Going this route requires editing your `system/user/config/config.php`  file; alternatively, you are encouraged to adopt any of the community-developed "bootstrap" methods, such as:

- [EE Master Config](https://github.com/focuslabllc/ee-master-config/tree/EE3) from [Focus Lab LLC](https://focuslabllc.com/)
- [This little-known gist](https://gist.github.com/airways/1329538) from [@airways](https://twitter.com/airways)

Default values are given below.

### Basic Preferences
#### Cache Path and Cache URL
Paths are assumed to be absolute, but will also test as relative to Base Path. If not configured, `cache_path` and `cache_url` will default to `/cache` at the root of your site.

```php
'cache_path' => '/path/to/example.com/cache'  
'cache_url' => 'https://example.com/cache'
```

#### Combine Assets & Minify
Specify the type of assets you want to combine and run through the minification engine.
```php
'combine_js'      => 'yes'
'combine_css'     => 'yes' 
'minify_css'      => 'yes'
'minify_js'       => 'yes'
'minify_html'     => 'no'
```
> Note: HTML minification is only available for EE2.4+

#### Disabling Hop Minifizer
Disable Hop Minifizer entirely; aborts all activity and returns all tags untouched.
```php
'disable'         => 'no'
```

### Advanced Preferences

The base path of your local source assets - _optional_
```php
'base_path'       => 'FCPATH'
```

Location on your webserver where your source CSS and JS files sit - _optional_
```php 
'base_url'        => 'base_url'
```

Updating `'cache_busting'` to a unique string forces Hop Minifizer to create a new cache file, ensuring that your most recent changes are visible to your end users immediately - _optional_
```php
'cache_busting'    => ''
```

When `'cleanup'` is enabled, Hop Minifizer will automatically delete any cache files it determines as expired - _optional_
```php
'cleanup'          => ''
```

Choose which algorithm will be used to create the cache filename. Available values: `'sha1'`, `'md5'`, and `'sanitize'/'sanitise'`
```php
'hash_method'      => 'sha1'
```

Rewrites relative image and @import URLs into absolute URLs 
```php
'css_prepend_mode' => 'yes'
```

The URL to use when `'css_prepend_url'` is `'yes'`
```php
'css_prepend_url'  => 'base_url'
```

Specifies which minification library to use for your CSS. **Available values:** `'minify'`, `'cssmin'`
```php
'css_library'      => 'minify'
``` 

Specifies which minification library to use for your JS. **Available values:** `'jsmin'`, `'jsminplus'`
```php
'js_library'       => 'jsmin'
``` 

Specify the method with which Hop Minifizer should fetch external & `{stylesheet=}` assets. **Available Values:** `'auto'`, `'fgc'`, `'curl'`
```php
'remote_mode'       => 'auto'
``` 

Specify the method with which Hop Minifizer should fetch external & `{stylesheet=}` assets. **Available Values:** `'auto'`, `'fgc'`, `'curl'`
```php
'remote_mode'       => 'auto'
``` 

Specify whether or not to generate gzip. Requires the Zlib compression library, and modifications to your `.htaccess` file.
```php
'save_gz'           => 'no'
``` 

Hook for HTML minification. If you have CE Cache installed, you may run HTML minification during its `ce_cache_pre_save` hook.
 Values: `'template_post_parse'`, `'ce_cache_pre_save'`
```php
'minify_html_hook'       => ''
``` 

### Amazon S3 Settings
If you are using Amazon Simple Storage Service (Amazon S3) to host your static website, enter your S3 access credentials here.
```php
'amazon_s3_access_key_id'     => ''
'amazon_s3_secret_access_key' => ''
'amazon_s3_bucket'            => ''
'amazon_s3_location'          => ''
'amazon_s3_folder'            => ''
```
For more info on Amazon S3, visit https://docs.aws.amazon.com/s3/.


### Config via Tags & Paramenters

> **Heads up!** All configuration values mentioned above can also be specified as tag parameters using `exp:hop_minifizer:css`, `exp:hop_minifizer:js`, and `exp:hop_minifizer:display`.  
>  
>Refer to the EE `$config` section above for key/value combinations.

<table class="table table-bordered table-striped">
    <caption><h3>exp:hop_minifizer:css <small>and</small> exp:hop_minifizer:js</h3></caption>
    <thead>
        <tr>
            <th>Parameter</th>
            <th>Required?</th>
            <th>Default</th>
            <th>Values</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>attribute:name="value"</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>By default Hop Minifizer uses the first tag it encounters as a template for the cache tag output (e.g. <code>&lt;link ... &gt;</code> or <code>&lt;script ... &gt;</code>). You may override this by specifying one or more tag attributes using this parameter format.</p>
            </td>
        </tr>
        <tr>
            <td><code>combine=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>yes | no</td>
            <td>
                <p>Shorthand, runtime override of the <code>combine_(css|js)</code> config option</p>
            </td>
        </tr>
        <tr>
            <td><code>delimiter=</code></td>
            <td>no</td>
            <td>,</td>
            <td>String</td>
            <td>
                <p>When not combining, this is the string to place between cache output</p>
            </td>
        </tr>
        <tr>
            <td rowspan="3"><code>display=</code></td>
            <td rowspan="3">no</td>
            <td rowspan="3">tag</td>
            <td>tag</td>
            <td>
                <p>Specify which format to display as the cache results; the default is "tag", returning the appropriate asset tag (e.g. <code>&lt;link ...&gt;</code> or <code>&lt;script...&gt;&lt;/script&gt;</code>).</p>
            </td>
        </tr>
        <tr>
            <td>url</td>
            <td>
                <p>This option will only return the URL to the cache, e.g. <code>http://example.com/cache/cachefile.name.ext</code>. When specifying URL, be aware that if Hop Minifizer encounters an error, you may experience unexpected results.</p>
            </td>
        </tr>
        <tr>
            <td>contents</td>
            <td>
                <p>Instruct Hop Minifizer to return the contents of the cache, for embedding inline in your template. By default, only the contents are returned; specify one or more <code>attribute:name="type"</code> values to instruct Hop Minifizer to wrap the contents in the appropriate HTML tag (e.g. <code>&lt;style&gt;...&lt;/style&gt;</code> or <code>&lt;script&gt;...&lt;/script&gt;</code></p>
            </td>
        </tr>
        <tr>
            <td><code>minify=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>yes | no</td>
            <td>
                <p>Shorthand, runtime override of the <code>minify_(css|js)</code> config option</p>
            </td>
        </tr>
        <tr>
            <td><code>priority=</code></td>
            <td>no</td>
            <td>0</td>
            <td>Numeric</td>
            <td>
                <p>For use with <code>queue</code> feature. Value specified is a number; lower numbers are placed earlier in queue order.</p>
            </td>
        </tr>
        <tr>
            <td><code>queue=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>To "queue" assets for output; receives a "key" value (e.g. <code>"head_css"</code>), which is used later to lookup and retrieve the queue'd cache</p>
            </td>
        </tr>
        <tr>
            <td><code>scss_templates=</code></td>
            <td>no</td>
            <td>none</td>
            <td>String</td>
            <td>If you are using the Sass to extend css in your stylesheets, <code>scss_templates=</code> allows you to specify your <code>SCSS</code> files for minifization (see the <strong>Basic Usage</strong> section below for examples of how to minifize <code>SCSS</code>)</td>
        </tr>
    </tbody>
</table>

<hr>
<br>

<table class="table table-bordered table-striped">
    <caption><h3>exp:hop_minifizer:display</h3></caption>
    <thead>
        <tr>
            <th>Parameter</th>
            <th>Required?</th>
            <th>Default</th>
            <th>Values</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>attribute:name="value"</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>By default Hop Minifizer uses the first tag it encounters as a template for the cache tag output (e.g. <code>&lt;link ... &gt;</code> or <code>&lt;script ... &gt;</code>). You may override this by specifying one or more tag attributes using this parameter format.</p>
            </td>
        </tr>
        <tr>
            <td><code>combine=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>yes | no</td>
            <td>
                <p>Shorthand, runtime override of the <code>combine_(css|js)</code> config option</p>
            </td>
        </tr>
        <tr class="warning">
            <td><code>css=</code> OR <code>js=</code></td>
            <td>yes</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>The "key" value of the queue, to fetch and return</p>
            </td>
        </tr>
        <tr>
            <td><code>delimiter=</code></td>
            <td>no</td>
            <td>,</td>
            <td>String</td>
            <td>
                <p>When not combining, this is the string to place between cache output</p>
            </td>
        </tr>
        <tr>
            <td rowspan="3"><code>display=</code></td>
            <td rowspan="3">no</td>
            <td rowspan="3">tag</td>
            <td>tag</td>
            <td>
                <p>Specify which format to display as the cache results; the default is "tag", returning the appropriate asset tag (e.g. <code>&lt;link ...&gt;</code> or <code>&lt;script...&gt;&lt;/script&gt;</code>).</p>
            </td>
        </tr>
        <tr>
            <td>url</td>
            <td>
                <p>This option will only return the URL to the cache, e.g. <code>http://example.com/cache/cachefile.name.ext</code>. When specifying URL, be aware that if Hop Minifizer encounters an error, you may experience unexpected results.</p>
            </td>
        </tr>
        <tr>
            <td>contents</td>
            <td>
                <p>Instruct Hop Minifizer to return the contents of the cache, for embedding inline in your template. By default, only the contents are returned; specify one or more <code>attribute:name="type"</code> values to instruct Hop Minifizer to wrap the contents in the appropriate HTML tag (e.g. <code>&lt;style&gt;...&lt;/style&gt;</code> or <code>&lt;script&gt;...&lt;/script&gt;</code></p>
            </td>
        </tr>
        <tr>
            <td><code>minify=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>yes | no</td>
            <td>
                <p>Shorthand, runtime override of the <code>minify_(css|js)</code> config option</p>
            </td>
        </tr>
    </tbody>
</table>

<hr>
<br>

<table class="table table-bordered table-striped">
    <caption><h3>exp:hop_minifizer</h3></caption>
    <thead>
        <tr>
            <th>Parameter</th>
            <th>Required?</th>
            <th>Default</th>
            <th>Values</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>attribute:name="value"</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>Override the tag output; useful if changing <code>display="contents"</code>, since you will need to specify tag output to avoid contents being returned without a containing tag. See <a href="#examples">Advanced Usage</a> section below.</p>
            </td>
        </tr>
        <tr>
            <td><code>combine=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>yes | no</td>
            <td>
                <p>Shorthand, runtime override of the <code>combine_(css|js)</code> config option</p>
            </td>
        </tr>
        <tr class="warning">
            <td><code>css=</code> OR <code>js=</code></td>
            <td>yes</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>Filename(s) of assets to cache. Cannot specify both in same call.</p>
            </td>
        </tr>
        <tr>
            <td><code>delimiter=</code></td>
            <td>no</td>
            <td>,</td>
            <td>String</td>
            <td>
                <p>When not combining, this is the string to place between cache output</p>
            </td>
        </tr>
        <tr>
            <td rowspan="3"><code>display=</code></td>
            <td rowspan="3">no</td>
            <td rowspan="3">tag</td>
            <td>tag</td>
            <td>
                <p>Specify which format to display as the cache results; the default is "tag", returning the appropriate asset tag (e.g. <code>&lt;link ...&gt;</code> or <code>&lt;script...&gt;&lt;/script&gt;</code>).</p>
            </td>
        </tr>
        <tr>
            <td>url</td>
            <td>
                <p>This option will only return the URL to the cache, e.g. <code>http://example.com/cache/cachefile.name.ext</code>. When specifying URL, be aware that if Hop Minifizer encounters an error, you may experience unexpected results.</p>
            </td>
        </tr>
        <tr>
            <td>contents</td>
            <td>
                <p>Instruct Hop Minifizer to return the contents of the cache, for embedding inline in your template. By default, only the contents are returned; specify one or more <code>attribute:name="type"</code> values to instruct Hop Minifizer to wrap the contents in the appropriate HTML tag (e.g. <code>&lt;style&gt;...&lt;/style&gt;</code> or <code>&lt;script&gt;...&lt;/script&gt;</code></p>
            </td>
        </tr>
        <tr>
            <td><code>minify=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>yes | no</td>
            <td>
                <p>Shorthand, runtime override of the minify_(css|js) config option</p>
            </td>
        </tr>
        <tr>
            <td><code>priority=</code></td>
            <td>no</td>
            <td>0</td>
            <td>Numeric</td>
            <td>
                <p>For use with <code>queue</code> feature. Value specified is a number; lower numbers are placed earlier in queue order.</p>
            </td>
        </tr>
        <tr>
            <td><code>queue=</code></td>
            <td>no</td>
            <td><em>none</em></td>
            <td>String</td>
            <td>
                <p>To "queue" assets for output; receives a "key" value (e.g. <code>"head_css"</code>), which is used later to lookup and retrieve the queue'd cache</p>
            </td>
        </tr>
    </tbody>
</table>

<hr>
<br>

<table class="table table-bordered table-striped">
    <caption><h3>exp:hop_minifizer:html</h3></caption>
    <thead>
        <tr>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <p>This tag is required only when all are true:</p>
                <ol>
                    <li>You have configured Hop Minifizer via EE's $config object</li>
                    <li>Hop Minifizer's extension is disabled</li>
                    <li>You are not using any of Hop Minifizer's other tags for CSS or JS processing</li>
                </ol>
                <p>When the above are all true, then Hop Minifizer needs to be called once during template parsing to know to minify your HTML. Placing this tag somewhere in your template will do just that.</p>
            </td>
        </tr>
    </tbody>
</table>

<hr>
<br>


## Examples of Usage

### Basic Usage
Once configured, basic use of Hop Minifizer is as simple and beautiful as:

#### CSS
```html
{exp:hop_minifizer:css}
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="{stylesheet='css/webfonts'}">
    <link rel="stylesheet" href="http://example.com/css/global.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="{path='site/site_style'}">
{/exp:hop_minifizer:css}
```
Will render something like:
```html
<link rel="stylesheet" href="http://example.com/cache/b488f65d0085dcc6b8f536f533b5f2da.1345797433.css">
```
#### JS
```html
{exp:hop_minifizer:js}
    <script type="text/javascript" src="/js/mylibs/jquery.easing.js"></script>
    <script type="text/javascript" src="/js/mylibs/jquery.cycle.js"></script>
    <script type="text/javascript" src="/js/mylibs/jquery.forms.js"></script>
    <script type="text/javascript" src="/js/scripts.js"></script>
    <script type="text/javascript" src="/js/plugins.js"></script>
{/exp:hop_minifizer:js}
 ```
Will render something like:
```html
<script type="text/javascript" src="http://example.com/cache/16b6345ae6f4b24dd2b1cba102cbf2fa.1298784512.js"></script>
```
#### SCSS
Include your SCSS files using the `data-parse-css` attribute in a `<link...>` tag:
```html
{exp:hop_minifizer:css}
    <link rel="stylesheet" href="{stylesheet='styles/hop-scss'}" data-parse-scss>
{/exp:hop_minifizer:css}
```
Or by using the `scss_templates` parameter:
```html
{exp:hop_minifizer:css scss_templates="styles/hop-scss"}
{/exp:hop_minifizer:css}
```

### Advanced Usage
There is a wealth of power and flexibility at your fingertips should you choose to go beyond Hop Minifizer's basic setup. This section will continue to grow with more examples as time goes on.
#### Queueing & Display
Hop Minifizer has the ability to "queue" assets and then display the final cache later in your EE template. It's a simple two-step process:

##### Step 1: Add `queue=` parameter to your `exp:hop_minifizer:css` or `exp:hop_minifizer:js` opening tag:
```html
{exp:hop_minifizer:css queue="head_css"}
    <link rel="stylesheet" href="css/reset.css">
{/exp:hop_minifizer:css}
```

For subsequent queue'ing, continue to use the same parameter value:
```html
{exp:hop_minifizer:css queue="head_css"}
    <link rel="stylesheet" href="{stylesheet='css/webfonts'}">
{/exp:hop_minifizer:css}
    ...
 
{exp:hop_minifizer:css queue="head_css"}
    <link rel="stylesheet" href="http://example.com/css/global.css">
    <link rel="stylesheet" href="css/forms.css">
{/exp:hop_minifizer:css}
```
##### Step 2: Once all assets have been queue'd, you can finally display your tag output with the `exp:hop_minifizer:display` tag; use `css=` or `js=` as your tag parameter depending on the type of asset, and specify the name of the queue:
```html
{exp:hop_minifizer:display css="head_css"}
 
{!-- will render something like: --}
<link rel="stylesheet" href="http://example.com/cache/b488f65d0085dcc6b8f536f533b5f2da.1345797433.css">

```

#### The 'Croxton Queue' for EE2.4+
[Mark Croxton](https://github.com/croxton) submitted this [feature request](http://devot-ee.com/add-ons/support/minimee/viewthread/4552#15417) to delay the processing of `exp:minimee:display` in the original Minimee until all other template parsing had been completed, by way of leveraging EE2.4's `template_post_parse` hook. It was a brilliant idea and indication of his mad scientist skills. In his wise words:

> "Then you would never need worry about parse order when injecting assets into the header or footer of your page using queues."

This, combined with the `priority=""` parameter, means you can do something like:
```html
{exp:hop_minifizer:display css="header_css"}
```
sometime LATER in EE's parse order...
 ```html
{exp:hop_minifizer:css queue="header_css" priority="10"}
    <link href="css/forms.css" rel="stylesheet" type="text/css" />
{/exp:hop_minifizer:css}
```
and even later in parse order, also note the priority...
```html
{exp:hop_minifizer:css queue="header_css" priority="0"}
    <link href="css/reset.css" rel="stylesheet" type="text/css" />
{/exp:hop_minifizer:css}
```
And then what ends up happening is that exp:hop_minifizer:display outputs a cached css that contains, in this order:

   1. `css/reset.css` (first because of `priority="0"`)  
   2. `css/forms.css` (second because of `priority="10"`)

#### Embedding Cache Contents Inline
There are now multiple approaches to "embedding" the contents of your cache inline with the rest of your HTML content. Let's cover 2 basic options:


##### Option 1: Using a Queue + Display method
Begin with **Step 1** above under Queueing + Display. This will queue one or more assets for display.

As a **Step 2**, specify a display output of `contents`. Be sure to wrap it within the appropriate HTML tag:

```html
<style type="text/css">
    {exp:hop_minifizer:display css="head_css" display="contents"}
</style>
```
However, by also specifying one or more `attribute:name="value"` parameters to `exp:hop:display`, Hop Minifizer will automatically wrap the contents of the cache in the appropriate HTML tag for you, using the attributes you specified:

```html
{exp:hop_minifizer:display css="head_css" display="contents" attribute:type="text/css"}
```
Will output something like:
```html
<style type="text/css">
    /* your CSS here */
</style>
```
##### Option 2: Directly changing display mode
The return mode of `exp:hop_minifizer:js` and `exp:hop_minifizer:css` tags can now be altered in a single call, by specifying the display value at the same time. When doing so, it is recommended to also provide the appropriate `attribute:name="value"` parameters as well, so that the contents are automatically wrapped in tags:
```html
{exp:hop_minifizer:css display="contents" attribute:type="text/css"}
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="{stylesheet='css/webfonts'}">
    <link rel="stylesheet" href="http://example.com/css/global.css">
    <link rel="stylesheet" href="css/forms.css">
{/exp:hop_minifizer:css}
 
{!-- Will output something like: --}
<style type="text/css">
    /* your CSS here */
</style>
```

All previous released versions would be available under releases.
