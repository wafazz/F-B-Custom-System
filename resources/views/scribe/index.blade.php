<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Star Coffee API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://star-coffee.test";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.9.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.9.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-user">
                                <a href="#endpoints-GETapi-user">GET api/user</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-branches--branch_id--menu">
                                <a href="#endpoints-GETapi-branches--branch_id--menu">Public read-only menu — branch-scoped, only available + in-stock items.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-orders">
                                <a href="#endpoints-POSTapi-orders">POST api/orders</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-orders--id-">
                                <a href="#endpoints-GETapi-orders--id-">GET api/orders/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-push-vapid-key">
                                <a href="#endpoints-GETapi-push-vapid-key">GET api/push/vapid-key</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-push-subscribe">
                                <a href="#endpoints-POSTapi-push-subscribe">POST api/push/subscribe</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-push-subscribe">
                                <a href="#endpoints-DELETEapi-push-subscribe">DELETE api/push/subscribe</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: May 8, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://star-coffee.test</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-user">GET api/user</h2>

<p>
</p>



<span id="example-requests-GETapi-user">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://star-coffee.test/api/user" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/user"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-user">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(self), microphone=(), camera=()
x-xss-protection: 0
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-user" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-user"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-user" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-user" data-method="GET"
      data-path="api/user"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-user', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-user"
                    onclick="tryItOut('GETapi-user');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-user"
                    onclick="cancelTryOut('GETapi-user');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-user"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/user</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-user"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-user"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-branches--branch_id--menu">Public read-only menu — branch-scoped, only available + in-stock items.</h2>

<p>
</p>



<span id="example-requests-GETapi-branches--branch_id--menu">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://star-coffee.test/api/branches/1/menu" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/branches/1/menu"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-branches--branch_id--menu">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(self), microphone=(), camera=()
x-xss-protection: 0
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;branch&quot;: {
        &quot;id&quot;: 1,
        &quot;code&quot;: &quot;SC-KLCC&quot;,
        &quot;name&quot;: &quot;Star Coffee &mdash; KLCC&quot;,
        &quot;sst_rate&quot;: 6,
        &quot;sst_enabled&quot;: true,
        &quot;status&quot;: &quot;active&quot;
    },
    &quot;categories&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Hot Coffee&quot;,
            &quot;slug&quot;: &quot;hot-coffee&quot;,
            &quot;icon&quot;: &quot;heroicon-o-fire&quot;,
            &quot;sort_order&quot;: 0,
            &quot;products&quot;: [
                {
                    &quot;id&quot;: 1,
                    &quot;sku&quot;: &quot;SC-ESP&quot;,
                    &quot;name&quot;: &quot;Espresso&quot;,
                    &quot;slug&quot;: &quot;espresso-sc-esp&quot;,
                    &quot;description&quot;: &quot;House-made Espresso crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 6,
                    &quot;base_price&quot;: 6,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 2,
                    &quot;sku&quot;: &quot;SC-AMR&quot;,
                    &quot;name&quot;: &quot;Americano&quot;,
                    &quot;slug&quot;: &quot;americano-sc-amr&quot;,
                    &quot;description&quot;: &quot;House-made Americano crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 8,
                    &quot;base_price&quot;: 8,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 3,
                    &quot;sku&quot;: &quot;SC-CAP&quot;,
                    &quot;name&quot;: &quot;Cappuccino&quot;,
                    &quot;slug&quot;: &quot;cappuccino-sc-cap&quot;,
                    &quot;description&quot;: &quot;House-made Cappuccino crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 12,
                    &quot;base_price&quot;: 12,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 4,
                    &quot;sku&quot;: &quot;SC-LAT&quot;,
                    &quot;name&quot;: &quot;Caffe Latte&quot;,
                    &quot;slug&quot;: &quot;caffe-latte-sc-lat&quot;,
                    &quot;description&quot;: &quot;House-made Caffe Latte crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 13,
                    &quot;base_price&quot;: 13,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 5,
                    &quot;sku&quot;: &quot;SC-FLW&quot;,
                    &quot;name&quot;: &quot;Flat White&quot;,
                    &quot;slug&quot;: &quot;flat-white-sc-flw&quot;,
                    &quot;description&quot;: &quot;House-made Flat White crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 13,
                    &quot;base_price&quot;: 13,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: false,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            &quot;id&quot;: 2,
            &quot;name&quot;: &quot;Cold Coffee&quot;,
            &quot;slug&quot;: &quot;cold-coffee&quot;,
            &quot;icon&quot;: &quot;heroicon-o-cube&quot;,
            &quot;sort_order&quot;: 1,
            &quot;products&quot;: [
                {
                    &quot;id&quot;: 6,
                    &quot;sku&quot;: &quot;SC-ILA&quot;,
                    &quot;name&quot;: &quot;Iced Latte&quot;,
                    &quot;slug&quot;: &quot;iced-latte-sc-ila&quot;,
                    &quot;description&quot;: &quot;House-made Iced Latte crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 14,
                    &quot;base_price&quot;: 14,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 7,
                    &quot;sku&quot;: &quot;SC-CBW&quot;,
                    &quot;name&quot;: &quot;Cold Brew&quot;,
                    &quot;slug&quot;: &quot;cold-brew-sc-cbw&quot;,
                    &quot;description&quot;: &quot;House-made Cold Brew crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 15,
                    &quot;base_price&quot;: 15,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: false,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 8,
                    &quot;sku&quot;: &quot;SC-IMC&quot;,
                    &quot;name&quot;: &quot;Iced Mocha&quot;,
                    &quot;slug&quot;: &quot;iced-mocha-sc-imc&quot;,
                    &quot;description&quot;: &quot;House-made Iced Mocha crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 16,
                    &quot;base_price&quot;: 16,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 4,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Tea &amp; Others&quot;,
            &quot;slug&quot;: &quot;tea-others&quot;,
            &quot;icon&quot;: &quot;heroicon-o-beaker&quot;,
            &quot;sort_order&quot;: 2,
            &quot;products&quot;: [
                {
                    &quot;id&quot;: 9,
                    &quot;sku&quot;: &quot;SC-ERG&quot;,
                    &quot;name&quot;: &quot;Earl Grey Tea&quot;,
                    &quot;slug&quot;: &quot;earl-grey-tea-sc-erg&quot;,
                    &quot;description&quot;: &quot;House-made Earl Grey Tea crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 9,
                    &quot;base_price&quot;: 9,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 2,
                    &quot;is_featured&quot;: false,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                },
                {
                    &quot;id&quot;: 10,
                    &quot;sku&quot;: &quot;SC-HCH&quot;,
                    &quot;name&quot;: &quot;Hot Chocolate&quot;,
                    &quot;slug&quot;: &quot;hot-chocolate-sc-hch&quot;,
                    &quot;description&quot;: &quot;House-made Hot Chocolate crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 12,
                    &quot;base_price&quot;: 12,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 2,
                    &quot;is_featured&quot;: false,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: [
                        {
                            &quot;id&quot;: 1,
                            &quot;name&quot;: &quot;Size&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: true,
                            &quot;min_select&quot;: 1,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 1,
                                    &quot;name&quot;: &quot;Regular&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 2,
                                    &quot;name&quot;: &quot;Large&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 2,
                            &quot;name&quot;: &quot;Sugar Level&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 3,
                                    &quot;name&quot;: &quot;No Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 4,
                                    &quot;name&quot;: &quot;Less Sugar&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 5,
                                    &quot;name&quot;: &quot;Standard&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 3,
                            &quot;name&quot;: &quot;Milk Type&quot;,
                            &quot;selection_type&quot;: &quot;single&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 1,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 6,
                                    &quot;name&quot;: &quot;Dairy&quot;,
                                    &quot;price_delta&quot;: 0,
                                    &quot;is_default&quot;: true
                                },
                                {
                                    &quot;id&quot;: 7,
                                    &quot;name&quot;: &quot;Oat&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 8,
                                    &quot;name&quot;: &quot;Almond&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 9,
                                    &quot;name&quot;: &quot;Soy&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        },
                        {
                            &quot;id&quot;: 4,
                            &quot;name&quot;: &quot;Add-ons&quot;,
                            &quot;selection_type&quot;: &quot;multiple&quot;,
                            &quot;is_required&quot;: false,
                            &quot;min_select&quot;: 0,
                            &quot;max_select&quot;: 4,
                            &quot;options&quot;: [
                                {
                                    &quot;id&quot;: 10,
                                    &quot;name&quot;: &quot;Extra Shot&quot;,
                                    &quot;price_delta&quot;: 3,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 11,
                                    &quot;name&quot;: &quot;Vanilla Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 12,
                                    &quot;name&quot;: &quot;Caramel Syrup&quot;,
                                    &quot;price_delta&quot;: 1.5,
                                    &quot;is_default&quot;: false
                                },
                                {
                                    &quot;id&quot;: 13,
                                    &quot;name&quot;: &quot;Whipped Cream&quot;,
                                    &quot;price_delta&quot;: 2,
                                    &quot;is_default&quot;: false
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            &quot;id&quot;: 4,
            &quot;name&quot;: &quot;Pastries&quot;,
            &quot;slug&quot;: &quot;pastries&quot;,
            &quot;icon&quot;: &quot;heroicon-o-cake&quot;,
            &quot;sort_order&quot;: 3,
            &quot;products&quot;: [
                {
                    &quot;id&quot;: 11,
                    &quot;sku&quot;: &quot;SC-BCR&quot;,
                    &quot;name&quot;: &quot;Butter Croissant&quot;,
                    &quot;slug&quot;: &quot;butter-croissant-sc-bcr&quot;,
                    &quot;description&quot;: &quot;House-made Butter Croissant crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 8,
                    &quot;base_price&quot;: 8,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 2,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: []
                },
                {
                    &quot;id&quot;: 12,
                    &quot;sku&quot;: &quot;SC-PCH&quot;,
                    &quot;name&quot;: &quot;Pain au Chocolat&quot;,
                    &quot;slug&quot;: &quot;pain-au-chocolat-sc-pch&quot;,
                    &quot;description&quot;: &quot;House-made Pain au Chocolat crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 9.5,
                    &quot;base_price&quot;: 9.5,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 2,
                    &quot;is_featured&quot;: false,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: []
                }
            ]
        },
        {
            &quot;id&quot;: 5,
            &quot;name&quot;: &quot;Cakes&quot;,
            &quot;slug&quot;: &quot;cakes&quot;,
            &quot;icon&quot;: &quot;heroicon-o-gift&quot;,
            &quot;sort_order&quot;: 4,
            &quot;products&quot;: [
                {
                    &quot;id&quot;: 13,
                    &quot;sku&quot;: &quot;SC-TIR&quot;,
                    &quot;name&quot;: &quot;Tiramisu Slice&quot;,
                    &quot;slug&quot;: &quot;tiramisu-slice-sc-tir&quot;,
                    &quot;description&quot;: &quot;House-made Tiramisu Slice crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 18,
                    &quot;base_price&quot;: 18,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 2,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: []
                },
                {
                    &quot;id&quot;: 14,
                    &quot;sku&quot;: &quot;SC-BCC&quot;,
                    &quot;name&quot;: &quot;Basque Cheesecake&quot;,
                    &quot;slug&quot;: &quot;basque-cheesecake-sc-bcc&quot;,
                    &quot;description&quot;: &quot;House-made Basque Cheesecake crafted by Star Coffee baristas.&quot;,
                    &quot;price&quot;: 19,
                    &quot;base_price&quot;: 19,
                    &quot;image&quot;: null,
                    &quot;gallery&quot;: null,
                    &quot;calories&quot;: null,
                    &quot;prep_time_minutes&quot;: 2,
                    &quot;is_featured&quot;: true,
                    &quot;sst_applicable&quot;: true,
                    &quot;modifier_groups&quot;: []
                }
            ]
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-branches--branch_id--menu" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-branches--branch_id--menu"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-branches--branch_id--menu"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-branches--branch_id--menu" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-branches--branch_id--menu">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-branches--branch_id--menu" data-method="GET"
      data-path="api/branches/{branch_id}/menu"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-branches--branch_id--menu', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-branches--branch_id--menu"
                    onclick="tryItOut('GETapi-branches--branch_id--menu');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-branches--branch_id--menu"
                    onclick="cancelTryOut('GETapi-branches--branch_id--menu');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-branches--branch_id--menu"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/branches/{branch_id}/menu</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-branches--branch_id--menu"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-branches--branch_id--menu"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>branch_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="branch_id"                data-endpoint="GETapi-branches--branch_id--menu"
               value="1"
               data-component="url">
    <br>
<p>The ID of the branch. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-orders">POST api/orders</h2>

<p>
</p>



<span id="example-requests-POSTapi-orders">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://star-coffee.test/api/orders" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"branch_id\": 16,
    \"order_type\": \"dine_in\",
    \"dine_in_table\": \"ngzmiyvdljnikhwa\",
    \"pickup_at\": \"2052-06-01\",
    \"notes\": \"n\",
    \"lines\": [
        {
            \"product_id\": 16,
            \"quantity\": 22,
            \"modifier_option_ids\": [
                16
            ],
            \"notes\": \"n\"
        }
    ],
    \"voucher_code\": \"b\",
    \"loyalty_redeem_points\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/orders"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "branch_id": 16,
    "order_type": "dine_in",
    "dine_in_table": "ngzmiyvdljnikhwa",
    "pickup_at": "2052-06-01",
    "notes": "n",
    "lines": [
        {
            "product_id": 16,
            "quantity": 22,
            "modifier_option_ids": [
                16
            ],
            "notes": "n"
        }
    ],
    "voucher_code": "b",
    "loyalty_redeem_points": 22
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-orders">
</span>
<span id="execution-results-POSTapi-orders" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-orders"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-orders"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-orders" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-orders">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-orders" data-method="POST"
      data-path="api/orders"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-orders', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-orders"
                    onclick="tryItOut('POSTapi-orders');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-orders"
                    onclick="cancelTryOut('POSTapi-orders');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-orders"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/orders</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>branch_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="branch_id"                data-endpoint="POSTapi-orders"
               value="16"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the branches table. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order_type"                data-endpoint="POSTapi-orders"
               value="dine_in"
               data-component="body">
    <br>
<p>Example: <code>dine_in</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pickup</code></li> <li><code>dine_in</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dine_in_table</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dine_in_table"                data-endpoint="POSTapi-orders"
               value="ngzmiyvdljnikhwa"
               data-component="body">
    <br>
<p>This field is required when <code>order_type</code> is <code>dine_in</code>. Must not be greater than 20 characters. Example: <code>ngzmiyvdljnikhwa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pickup_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="pickup_at"                data-endpoint="POSTapi-orders"
               value="2052-06-01"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>now</code>. Example: <code>2052-06-01</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-orders"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>lines</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Must have at least 1 items.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>product_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lines.0.product_id"                data-endpoint="POSTapi-orders"
               value="16"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the products table. Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lines.0.quantity"                data-endpoint="POSTapi-orders"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 99. Example: <code>22</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>modifier_option_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lines.0.modifier_option_ids[0]"                data-endpoint="POSTapi-orders"
               data-component="body">
        <input type="number" style="display: none"
               name="lines.0.modifier_option_ids[1]"                data-endpoint="POSTapi-orders"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the modifier_options table.</p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.notes"                data-endpoint="POSTapi-orders"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>n</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voucher_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="voucher_code"                data-endpoint="POSTapi-orders"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 40 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>loyalty_redeem_points</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="loyalty_redeem_points"                data-endpoint="POSTapi-orders"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 100000. Example: <code>22</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-orders--id-">GET api/orders/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-orders--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://star-coffee.test/api/orders/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/orders/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-orders--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(self), microphone=(), camera=()
x-xss-protection: 0
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;order&quot;: {
        &quot;id&quot;: 1,
        &quot;number&quot;: &quot;SCKLCC-260508-0001&quot;,
        &quot;branch_id&quot;: 1,
        &quot;status&quot;: &quot;preparing&quot;,
        &quot;status_label&quot;: &quot;Preparing&quot;,
        &quot;order_type&quot;: &quot;pickup&quot;,
        &quot;dine_in_table&quot;: null,
        &quot;pickup_at&quot;: null,
        &quot;subtotal&quot;: 32.5,
        &quot;sst_amount&quot;: 1.95,
        &quot;total&quot;: 34.45,
        &quot;payment_status&quot;: &quot;paid&quot;,
        &quot;payment_method&quot;: &quot;stub&quot;,
        &quot;payment_reference&quot;: &quot;STUB-2BE1C5ZNTF&quot;,
        &quot;notes&quot;: null,
        &quot;created_at&quot;: &quot;2026-05-08T16:15:28+00:00&quot;,
        &quot;items&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;product_id&quot;: 11,
                &quot;product_name&quot;: &quot;Butter Croissant&quot;,
                &quot;product_sku&quot;: &quot;SC-BCR&quot;,
                &quot;unit_price&quot;: 8,
                &quot;quantity&quot;: 1,
                &quot;line_total&quot;: 8,
                &quot;notes&quot;: null,
                &quot;modifiers&quot;: []
            },
            {
                &quot;id&quot;: 2,
                &quot;product_id&quot;: 12,
                &quot;product_name&quot;: &quot;Pain au Chocolat&quot;,
                &quot;product_sku&quot;: &quot;SC-PCH&quot;,
                &quot;unit_price&quot;: 9.5,
                &quot;quantity&quot;: 1,
                &quot;line_total&quot;: 9.5,
                &quot;notes&quot;: null,
                &quot;modifiers&quot;: []
            },
            {
                &quot;id&quot;: 3,
                &quot;product_id&quot;: 7,
                &quot;product_name&quot;: &quot;Cold Brew&quot;,
                &quot;product_sku&quot;: &quot;SC-CBW&quot;,
                &quot;unit_price&quot;: 15,
                &quot;quantity&quot;: 1,
                &quot;line_total&quot;: 15,
                &quot;notes&quot;: null,
                &quot;modifiers&quot;: [
                    {
                        &quot;group_name&quot;: &quot;Size&quot;,
                        &quot;option_name&quot;: &quot;Regular&quot;,
                        &quot;price_delta&quot;: 0
                    },
                    {
                        &quot;group_name&quot;: &quot;Sugar Level&quot;,
                        &quot;option_name&quot;: &quot;Standard&quot;,
                        &quot;price_delta&quot;: 0
                    },
                    {
                        &quot;group_name&quot;: &quot;Milk Type&quot;,
                        &quot;option_name&quot;: &quot;Dairy&quot;,
                        &quot;price_delta&quot;: 0
                    }
                ]
            }
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-orders--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-orders--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-orders--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-orders--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-orders--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-orders--id-" data-method="GET"
      data-path="api/orders/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-orders--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-orders--id-"
                    onclick="tryItOut('GETapi-orders--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-orders--id-"
                    onclick="cancelTryOut('GETapi-orders--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-orders--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/orders/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-orders--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-orders--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-orders--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the order. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-push-vapid-key">GET api/push/vapid-key</h2>

<p>
</p>



<span id="example-requests-GETapi-push-vapid-key">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://star-coffee.test/api/push/vapid-key" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/push/vapid-key"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-push-vapid-key">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: geolocation=(self), microphone=(), camera=()
x-xss-protection: 0
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;public_key&quot;: &quot;BOQ_2H4AvzAcrstFJ_rv2TmHQ7zgAwym-3tJG0aHczC2yNgLyGPZqMj4maLdHIetqAJwru9B0-D7k4mB-GiRIMI&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-push-vapid-key" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-push-vapid-key"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-push-vapid-key"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-push-vapid-key" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-push-vapid-key">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-push-vapid-key" data-method="GET"
      data-path="api/push/vapid-key"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-push-vapid-key', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-push-vapid-key"
                    onclick="tryItOut('GETapi-push-vapid-key');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-push-vapid-key"
                    onclick="cancelTryOut('GETapi-push-vapid-key');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-push-vapid-key"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/push/vapid-key</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-push-vapid-key"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-push-vapid-key"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-push-subscribe">POST api/push/subscribe</h2>

<p>
</p>



<span id="example-requests-POSTapi-push-subscribe">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://star-coffee.test/api/push/subscribe" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"endpoint\": \"b\",
    \"keys\": {
        \"p256dh\": \"architecto\",
        \"auth\": \"architecto\"
    },
    \"content_encoding\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/push/subscribe"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "endpoint": "b",
    "keys": {
        "p256dh": "architecto",
        "auth": "architecto"
    },
    "content_encoding": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-push-subscribe">
</span>
<span id="execution-results-POSTapi-push-subscribe" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-push-subscribe"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-push-subscribe"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-push-subscribe" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-push-subscribe">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-push-subscribe" data-method="POST"
      data-path="api/push/subscribe"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-push-subscribe', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-push-subscribe"
                    onclick="tryItOut('POSTapi-push-subscribe');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-push-subscribe"
                    onclick="cancelTryOut('POSTapi-push-subscribe');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-push-subscribe"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/push/subscribe</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-push-subscribe"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-push-subscribe"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>endpoint</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="endpoint"                data-endpoint="POSTapi-push-subscribe"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>keys</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>p256dh</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="keys.p256dh"                data-endpoint="POSTapi-push-subscribe"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>auth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="keys.auth"                data-endpoint="POSTapi-push-subscribe"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>content_encoding</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="content_encoding"                data-endpoint="POSTapi-push-subscribe"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 30 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-push-subscribe">DELETE api/push/subscribe</h2>

<p>
</p>



<span id="example-requests-DELETEapi-push-subscribe">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://star-coffee.test/api/push/subscribe" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://star-coffee.test/api/push/subscribe"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-push-subscribe">
</span>
<span id="execution-results-DELETEapi-push-subscribe" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-push-subscribe"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-push-subscribe"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-push-subscribe" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-push-subscribe">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-push-subscribe" data-method="DELETE"
      data-path="api/push/subscribe"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-push-subscribe', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-push-subscribe"
                    onclick="tryItOut('DELETEapi-push-subscribe');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-push-subscribe"
                    onclick="cancelTryOut('DELETEapi-push-subscribe');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-push-subscribe"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/push/subscribe</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-push-subscribe"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-push-subscribe"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
