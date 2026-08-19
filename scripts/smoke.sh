#!/usr/bin/env bash
# Smoke tests for slapbabydesigns.co.za.
#
# This project deploys straight to production on merge and has no staging, so
# this script IS the staging gate. Most of what it checks cannot be caught any
# other way: `apachectl configtest` does not validate .htaccess, `php -l` does
# not notice that a rewrite is missing, and nothing but a real request proves
# that lib/config.php is not downloadable.
#
#   docker build -t slap-test .
#   docker run -d --rm -p 8099:80 --name slap-test slap-test
#   bash scripts/smoke.sh http://localhost:8099
#   docker stop slap-test
#
# Against production, read-only checks only — the POST section writes real
# enquiries to the data volume, so do not point it at the live site.

set -uo pipefail
BASE="${1:-http://localhost:8099}"
PASS=0; FAIL=0

pass() { printf '  \033[32mPASS\033[0m  %s\n' "$1"; PASS=$((PASS + 1)); }
fail() { printf '  \033[31mFAIL\033[0m  %s\n' "$1"; FAIL=$((FAIL + 1)); }

# status <path> <expected-codes-csv> [description]
status() {
    local path="$1" want="$2" desc="${3:-$1}" got
    got=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$BASE$path")
    if [[ ",$want," == *",$got,"* ]]; then
        pass "$desc -> $got"
    else
        fail "$desc -> got $got, wanted one of $want"
    fi
}

# header <path> <header-name> <substring>
header() {
    local path="$1" name="$2" want="$3" got
    got=$(curl -sI --max-time 15 "$BASE$path" | tr -d '\r' | grep -i "^$name:" | head -1)
    if [[ "$got" == *"$want"* ]]; then
        pass "$name on $path contains '$want'"
    else
        fail "$name on $path: got '${got:-<absent>}', wanted '$want'"
    fi
}

# contains <path> <substring> <description>
contains() {
    local path="$1" want="$2" desc="$3" body
    body=$(curl -s --max-time 15 "$BASE$path")
    if [[ "$body" == *"$want"* ]]; then
        pass "$desc"
    else
        fail "$desc — '$want' not found in $path"
    fi
}

# no_php_noise <label> <body> — display_errors must be off in production. A
# leaked notice is both an information disclosure and a broken-looking page.
no_php_noise() {
    local label="$1" body="$2"
    if [[ "$body" != *"Warning:"* && "$body" != *"Notice:"* && "$body" != *"<br />"* ]]; then
        pass "no PHP notice/warning markup in $label"
    else
        fail "PHP notice/warning markup leaked into $label"
    fi
}

echo "-- Smoke tests against $BASE --"

echo "> Source and secret exposure"
# The Dockerfile does COPY . /var/www/html/, so the build context IS the public
# webroot. Every path here has been publicly downloadable at some point in some
# project; .dockerignore and .htaccess are the only two things stopping it.
status /.git/HEAD          403,404 ".git must not be served"
status /composer.json      403,404 "composer.json must not be served"
status /composer.lock      403,404 "composer.lock must not be served"
status /lib/config.php     403,404 "lib/config.php must not be served"
status /partials/head.php  403,404 "partials/ must not be served"
status /docker/php-prod.ini 403,404 "docker/ must not be in the image"
status /scripts/smoke.sh   403,404 "scripts/ must not be in the image"
status /vendor/autoload.php 403,404 "vendor/ must not be browsable"

echo "> Pages serve"
# Derived from the sitemap, which lib/pages.php generates, rather than typed out
# here. A hand-kept list is a second copy of the manifest, and the copy is what
# goes stale: /privacy/ shipped without a single smoke check because nobody
# remembered to add a line to this file. Now a page cannot exist without being
# tested, and a page that vanishes from the sitemap fails the count check below.
sitemap_urls=$(curl -s --max-time 20 "$BASE/sitemap.xml"     | grep -o '<loc>[^<]*</loc>' | sed 's|</\?loc>||g')

if [[ -z "$sitemap_urls" ]]; then
    fail "sitemap.xml yielded no URLs — cannot derive the page list"
else
    n=0
    while read -r url; do
        [[ -z "$url" ]] && continue
        n=$((n+1))
        # The sitemap carries absolute production URLs; test them against
        # whatever host this run targets.
        path="/${url#*://*/}"
        status "$path" 200 "sitemap page $path"
    done <<< "$sitemap_urls"
    if [[ "$n" -ge 4 ]]; then pass "sitemap lists $n pages (>=4)"
    else fail "sitemap lists only $n pages — expected at least 4"; fi
fi

# Query-string variants are not sitemap entries, so they stay explicit.
status /gallery/?made=character 200 "gallery filtered to character bears"
status /gallery/?made=kit       200 "gallery filtered to supporters' kit"

echo "> 404 handling"
# ErrorDocument must reach 404.php. Apache's own error page would be a 404 with
# no nav, no branding and no way back into the site.
status /no-such-page-here 404 "nonsense URL returns 404"
contains /no-such-page-here "A dropped stitch" "404 is the styled page, not Apache's"

echo "> Crawler surface"
home=$(curl -s --max-time 15 "$BASE/")
status /robots.txt       200 "robots.txt"
header /robots.txt       Content-Type text/plain
status /sitemap.xml      200 "sitemap.xml (rewritten to sitemap.php)"
header /sitemap.xml      Content-Type xml
contains /sitemap.xml    "<loc>" "sitemap.xml lists at least one URL"
# Each generated file answers on its own .php name too, so without this it
# serves identical bytes at two URLs and a crawler that finds the .php form
# indexes it as a second page. The redirect has to fire ONLY for requests from
# outside -- if it caught the internal rewrite as well, the pretty URL would
# loop, which is why the pretty ones are re-checked for zero redirects below.
for pair in "sitemap.php:/sitemap.xml" "robots.php:/robots.txt" "site-webmanifest.php:/site.webmanifest"; do
    src_php="/${pair%%:*}" want="${pair##*:}"
    read -r code location < <(curl -s -o /dev/null --max-time 15         -w '%{http_code} %{redirect_url}' "$BASE$src_php")
    if [[ "$code" == "301" && "$location" == *"$want" ]]; then
        pass "$src_php redirects to $want"
    else
        fail "$src_php -> $code $location, wanted 301 to $want"
    fi
done

for pretty in /sitemap.xml /robots.txt /site.webmanifest; do
    hops=$(curl -s -o /dev/null -L --max-redirs 5 --max-time 15 -w '%{num_redirects}' "$BASE$pretty")
    [[ "$hops" == "0" ]] && pass "$pretty is served directly, not via a redirect"                          || fail "$pretty took $hops redirect(s) -- the canonical rules are looping"
done

header /site.webmanifest Content-Type manifest+json
status /site.webmanifest 200 "site.webmanifest"

# robots.txt and the manifest are generated now, and both used to state things
# that could disagree with the rest of the site. The Sitemap: line named the
# apex host even when this image answered as slap.yellowarcher.co.za, so the
# check is that it points at the SAME origin the page calls canonical -- which
# holds on whichever hostname the suite is aimed at.
origin=$(printf '%s' "$(curl -s --max-time 15 "$BASE/")"          | grep -o 'rel="canonical" href="https\?://[^/"]*' | sed 's/.*href="//' | head -1)
robots_sitemap=$(curl -s --max-time 15 "$BASE/robots.txt" | grep -i '^Sitemap:' | awk '{print $2}')
if [[ -n "$origin" && "$robots_sitemap" == "$origin"/* ]]; then
    pass "robots.txt advertises a sitemap on the canonical origin ($robots_sitemap)"
else
    fail "robots.txt Sitemap '$robots_sitemap' is not on the canonical origin '$origin'"
fi

# The manifest is the last place the icons could still be referenced by their
# unhashed, one-day-cached paths. It is also the file whose description had
# drifted from the JSON-LD, which is why both are now read from one constant.
manifest=$(curl -s --max-time 15 "$BASE/site.webmanifest")
if printf '%s' "$manifest" | grep -q 'mark\.[a-f0-9]\{8,32\}\.svg'; then
    pass "manifest icons use content-hashed URLs"
else
    fail "manifest icons are not content-hashed"
fi
man_desc=$(printf '%s' "$manifest" | grep -o '"description": "[^"]*"' | head -1)
ld_desc=$(printf '%s' "$home" | grep -o '"description": "[^"]*"' | head -1)
if [[ -n "$man_desc" && "$man_desc" == "$ld_desc" ]]; then
    pass "manifest and JSON-LD give the same description"
else
    fail "manifest description $man_desc disagrees with JSON-LD $ld_desc"
fi

echo "> Stylesheet"
# The href is a hash of the CSS sources; .htaccess rewrites it back to
# assets/css.php?v=<hash>. If that rewrite is missing the page 404s its own
# stylesheet and renders unstyled — a total failure that still returns 200.
css=$(printf '%s' "$home" | grep -o '/assets/site\.[a-f0-9]\{1,32\}\.css' | head -1)

if [[ -z "$css" ]]; then
    fail "no /assets/site.<hash>.css href found in the homepage HTML"
else
    pass "stylesheet href in HTML: $css"
    status "$css" 200 "versioned stylesheet"
    header "$css" Content-Type  text/css
    header "$css" Cache-Control immutable
    # A token from tokens.css: proves the bundle actually concatenated the
    # parts rather than serving an empty or half-built response.
    contains "$css" "--ink" "bundle contains a tokens.css custom property"
fi

# A stale hash must be answered with current CSS but WITHOUT the immutable
# header. Caching a wrong URL for a year pins the mistake in every browser that
# saw it, and there is no way to reach those visitors again.
stale=$(curl -sI --max-time 15 "$BASE/assets/site.deadbeef.css" | tr -d '\r' | grep -i '^cache-control:' | head -1)
if [[ "$stale" != *immutable* ]]; then
    pass "stale hash is not cached as immutable"
else
    fail "stale hash came back immutable: $stale"
fi

echo "> Brand assets"
# The mark and the touch icon change WITHOUT changing filename, unlike a photo.
# Served at a fixed URL and marked immutable, redrawing the logo reaches nobody
# who has already visited — a browser holding an immutable entry does not
# revalidate it. So the pages reference a hashed URL, and the two forms need
# opposite policies. None of this is visible on a fresh browser: it is invisible
# precisely to whoever is testing, which is why it is asserted here.
brand_cc() { curl -sI --max-time 15 "$BASE$1" | tr -d '' | grep -i '^cache-control:' | head -1; }

# The plain path must stay short: a fixed URL whose contents change.
for asset in /assets/brand/mark.svg /assets/brand/apple-touch-icon.png; do
    status "$asset" 200 "brand asset $asset"
    cc=$(brand_cc "$asset")
    if [[ "$cc" == *immutable* ]]; then
        fail "$asset is immutable, so a redraw would never reach a returning visitor: $cc"
    else
        pass "$asset is not cached as immutable"
    fi
done

# The hashed path is what the pages actually load. An unhashed <img src> would
# mean the fix is not wired up at all, and nothing else here would notice.
hashed=$(printf '%s' "$home" | grep -o '/assets/brand/mark\.[a-f0-9]\{8,32\}\.svg' | head -1)
if [[ -z "$hashed" ]]; then
    fail "the masthead does not reference a hashed mark URL"
else
    pass "masthead references hashed mark: $hashed"
    status "$hashed" 200 "hashed mark rewrites back to the file"
    header "$hashed" Content-Type image/svg+xml
    cc=$(brand_cc "$hashed")
    if [[ "$cc" == *immutable* ]]; then
        pass "hashed mark is cached as immutable"
    else
        fail "hashed mark is not immutable, so the hash buys nothing: $cc"
    fi
fi

echo "> Fonts and third parties"
# /privacy/ tells visitors that nothing follows them between pages. That is a
# claim about what the HTML references, so it is asserted here rather than
# trusted: the fonts came from fonts.googleapis.com until they were brought
# in-house, and putting a CDN link back would quietly make the privacy notice
# false again without breaking anything a person would notice.
for asset in /assets/fonts/karla-latin.woff2 /assets/fonts/baloo2-latin.woff2; do
    status "$asset" 200 "font $asset"
    header "$asset" Cache-Control immutable
done

third_party=$(printf '%s' "$home" | grep -o 'https://[a-z0-9.-]*' | sort -u               | grep -v 'schema\.org' | grep -v 'slapbabydesigns\.co\.za' || true)
if [[ -z "$third_party" ]]; then
    pass "homepage references no third-party host"
else
    fail "homepage references third-party host(s): $(printf '%s' "$third_party" | tr '
' ' ')"
fi

echo "> Headers"
header / X-Content-Type-Options nosniff
header / Referrer-Policy        strict-origin
# Enforced, not Report-Only. The policy spent its life as Report-Only with no
# report-uri, so nothing collected the violations it existed to gather. The
# difference between the two header names is invisible on a working page, which
# is exactly why it is pinned here.
header / Content-Security-Policy "default-src 'self'"
header / Content-Security-Policy "style-src 'self';"
header / Content-Security-Policy "font-src 'self';"

echo "> No leaked PHP diagnostics"
no_php_noise "/"    "$home"
while read -r url; do
    [[ -z "$url" ]] && continue
    path="/${url#*://*/}"
    [[ "$path" == "/" ]] && continue
    no_php_noise "$path" "$(curl -s --max-time 15 "$BASE$path")"
done <<< "$sitemap_urls"
no_php_noise "/404" "$(curl -s --max-time 15 "$BASE/no-such-page-here")"

echo "> Enquiry form contract"
form=$(curl -s --max-time 15 "$BASE/enquiry/")

# WHY these three assertions exist, and why they are not negotiable:
# a honeypot named "company_website" (or "address", or anything else a browser
# recognises) gets filled in by Chrome autofill on a real visitor's form. The
# handler then treats a genuine enquiry as a bot and drops it — behind a success
# message, with no error, no bounce and no record anywhere. It is invisible
# until a customer phones to ask why nobody ever replied. The field must be
# named something meaningless that no autofill heuristic will ever match.
[[ "$form" == *'name="slap_ref"'* ]] \
    && pass "honeypot is named slap_ref" \
    || fail "honeypot field name=\"slap_ref\" is missing from the form"
[[ "$form" != *'name="company_website"'* ]] \
    && pass "honeypot is not named company_website (Chrome autofills that)" \
    || fail "honeypot named company_website — Chrome will fill it and real enquiries will vanish"
[[ "$form" != *'name="address"'* ]] \
    && pass "no autofill-bait 'address' field" \
    || fail "a field named address will be autofilled — rename it"

[[ "$form" == *'name="slap_t"'* ]] \
    && pass "render-time field slap_t is present" \
    || fail "slap_t timing field missing — the too-fast check cannot work"

# A caught bot must be answered exactly as a person is: same 303, same
# destination. Any difference tells the next version of the script which check
# caught it.
# Apache expands a relative Location into an absolute URL before it leaves the
# server, so match the tail of the destination rather than the header verbatim.
read -r trap_code trap_dest < <(curl -s -o /dev/null --max-time 15 -X POST "$BASE/enquiry/" \
    -w '%{http_code} %{redirect_url}' \
    --data-urlencode 'slap_ref=http://spam.example/' \
    --data-urlencode "slap_t=$(( $(date +%s) - 30 ))" \
    --data-urlencode 'name=Smoke Test' \
    --data-urlencode 'email=smoke@example.com' \
    --data-urlencode 'message=This is an automated smoke test submission.')
[[ "$trap_code" == "303" && "$trap_dest" == *"/enquiry/?sent=1" ]] \
    && pass "honeypot POST answers 303 to /enquiry/?sent=1" \
    || fail "honeypot POST answered $trap_code -> ${trap_dest:-<no redirect>}"

# Nobody fills a five-field form in under four seconds. Same silent 303.
read -r fast_code fast_dest < <(curl -s -o /dev/null --max-time 15 -X POST "$BASE/enquiry/" \
    -w '%{http_code} %{redirect_url}' \
    --data-urlencode "slap_t=$(date +%s)" \
    --data-urlencode 'name=Smoke Test' \
    --data-urlencode 'email=smoke@example.com' \
    --data-urlencode 'message=This is an automated smoke test submission.')
[[ "$fast_code" == "303" && "$fast_dest" == *"/enquiry/?sent=1" ]] \
    && pass "instant POST answers 303 to /enquiry/?sent=1" \
    || fail "too-fast POST answered $fast_code -> ${fast_dest:-<no redirect>}"

# A real person who leaves fields blank must get the form back with the errors
# in it, not a thank-you page and not a redirect that throws away what they
# typed. 422 rather than 200 so a broken form shows up in the access log.
bad_code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 -X POST "$BASE/enquiry/" \
    --data-urlencode "slap_t=$(( $(date +%s) - 30 ))" \
    --data-urlencode 'name=' --data-urlencode 'email=' --data-urlencode 'message=')
[[ "$bad_code" == "422" ]] \
    && pass "empty submission returns 422" \
    || fail "empty submission returned $bad_code, wanted 422"

bad_body=$(curl -s --max-time 15 -X POST "$BASE/enquiry/" \
    --data-urlencode "slap_t=$(( $(date +%s) - 30 ))" \
    --data-urlencode 'name=' --data-urlencode 'email=' --data-urlencode 'message=')
[[ "$bad_body" == *"Add your name"* ]] \
    && pass "empty submission re-renders the form with its errors" \
    || fail "empty submission did not show the 'Add your name' error"
no_php_noise "the rejected POST response" "$bad_body"

contains "/enquiry/?sent=1" "Sounds like a plan" "success page confirms the enquiry"

echo
printf 'passed %d, failed %d\n' "$PASS" "$FAIL"
[[ "$FAIL" -eq 0 ]] || exit 1
