<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="format-detection" content="telephone=no, date=no, email=no, address=no">
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/source-serif-4-latin-400.woff2') }}" crossorigin>
    @php(do_action('get_header'))
    @php(wp_head())

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    <div id="app">
      <a class="sr-only focus:not-sr-only" href="#main">
        {{ __('Skip to content', 'sage') }}
      </a>

      @include('sections.header')

      <main id="main" class="main">
        @yield('content')
      </main>

      @include('sections.footer')
    </div>

    {{-- Matomo Analytics (cookieless, deferred to idle, Trusted Types compatible) --}}
    <script nonce="{{ $cspNonce }}">
      (function () {
        var matomoPolicy = window.trustedTypes && window.trustedTypes.createPolicy
          ? window.trustedTypes.createPolicy('matomo', {
              createScriptURL: function (url) {
                if (url === '{!! esc_js(site_url('/wp-content/uploads/matomo/matomo.js')) !!}') return url;
                throw new TypeError('Blocked script URL: ' + url);
              }
            })
          : null;

        function initTracking() {
          var _paq = window._paq = window._paq || [];
          _paq.push(['disableCookies']);
          _paq.push(['trackPageView']);
          _paq.push(['enableLinkTracking']);
          _paq.push(['alwaysUseSendBeacon']);
          _paq.push(['setTrackerUrl', '{!! esc_js(site_url('/wp-content/plugins/matomo/app/matomo.php')) !!}']);
          _paq.push(['setSiteId', '1']);
          var g = document.createElement('script');
          var src = '{!! esc_js(site_url('/wp-content/uploads/matomo/matomo.js')) !!}';
          g.async = true;
          g.src = matomoPolicy ? matomoPolicy.createScriptURL(src) : src;
          document.head.appendChild(g);
        }

        function deferTracking() {
          'requestIdleCallback' in window
            ? requestIdleCallback(initTracking)
            : setTimeout(initTracking, 0);
        }

        if (document.prerendering) {
          document.addEventListener('prerenderingchange', deferTracking, { once: true });
        } else if (document.readyState === 'complete') {
          deferTracking();
        } else {
          window.addEventListener('load', deferTracking, { once: true });
        }
      })();
    </script>
    <noscript><img referrerpolicy="no-referrer-when-downgrade" src="{!! esc_url(site_url('/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1')) !!}" style="border:0;position:absolute;width:0;height:0" alt=""></noscript>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
