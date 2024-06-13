# silverstripe-simple-template-caching

Basic Caching Functionality For Page Templates

# make an exception

add the following to your Page (or Home Page or whatever) Controller:

```php
function canCachePage() : bool
{
     return false;
}
function CacheKeyContentCustom() : bool
{
     return 'extra-cache-key-goes-here';
}
```

# usage

Here is how to use it in the Page.ss file (or similar):

The if statements (e.g. `HasCacheKeyMenu`) you can leave out if you want to cache even if there are
unique things happening on the page e.g. user logged in, request vars, etc...These unique things will
be taken into account when the cache is created.

## low to high risk caching

1. add the `HasMyCacheKey...` only cached for simple page requests (e.g. not logged in, no get variables, etc...)
2. without the `HasMyCacheKey...` you are caching for all requests, including logged in users, get variables, etc...
3. for something that always stays the same, you can use something like: `$CacheKeyMenu(false, true)`
   which is cached the same for all pages (first parameter), and ignore get requests (second parameter).

```html

<!doctype html>
<html lang="$ContentLocale">
<head>
    <title>example</title>
</head>
<body>

    <% if $HasCacheKeyHeader %>
    <% cached $CacheKeyHeader %>
        <!-- cached header, one per site! -->
        <% include Header %>
    <% end_cached %>
    <% else %>
        <% include Header %>
    <% end_if %>

    <% if $HasCacheKeyMenu %>
        <% cached $CacheKeyMenu %>
            <!-- cached menu unique for each page -->
            <% include MenuDifferentPerPage %>
        <% end_cached %>
    <% else %>
        <% include MenuDifferentPerPage %>
    <% end_if %>

    <% cached $CacheKeyMenu(false, true) %>
        <!-- cached menu the same for whole site, no matter what! -->
        <% include AlwaysTheSameMenu %>
    <% end_cached %>

    <% cached $CacheKeyMenu(false) %>
        <!-- 
         cached menu the same for whole site, 
         takes url, request vars and member details into account 
         -->
        <% include AlwaysTheSameMenu %>
    <% end_cached %>

        
    <% if $HasCacheKeyContent %>
        <% cached $CacheKeyContent %>
            <!-- cached content unique for each page -->
            $Layout
        <% end_cached %>
    <% else %>
        $Layout
    <% end_if %>

    <% if $HasCacheKeyFooter %>
    <% cached $CacheKeyFooter %>
        <!-- cached footer, one per site -->
        <% include Footer %>
    <% end_cached %>
    <% else %>
        <% include Footer %>
    <% end_if %>

    <% cached $CacheKeyFooter(false, true) %>
        <!-- cached footer, one per site, no matter the request variables, user logged in / whatever! -->
        <% include Footer %>
    <% end_cached %>


</body>
</html>

```
