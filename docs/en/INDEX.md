# tl;dr

Caching is a not a simple matter.  You want to cache as much as you can without caching things that should not be cached.

This module looks at several caching options:

1. *Simplified Template Caching*.  This is not specific to this module, but it helps you make it easy.
   To use this module, basically check out the template ideas below and then
   review the caching details in the `siteconfig` (SiteConfig) to tune it.
   You can also add your own caching rules, etc...

2. *Whole Page Caching*.  This can be used in conjunction with a CDN.
   Check the `admin/settings` (SiteConfig) for details. Use with care.

3. *Resource Caching* 
    Check the `admin/settings` (SiteConfig) for details. Use with care.

## simplified template caching

Here is how to use it in the Page.ss file (or similar):

The if statements (e.g. `HasCacheKeyMenu`) you can leave out if you want to cache even if there are
unique things happening on the page e.g. user logged in, request vars, etc...These unique things will
be taken into account when the cache is created.

### low to high risk caching

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
    
    <!-- the first param is if it is unique for every page, the second forces caching even if there are get variables, etc... -->
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

    <!-- the first param is if it is unique for every page, the second forces caching even if there are get variables, etc... -->
    <% cached $CacheKeyFooter(false, true) %>
        <!-- cached footer, one per site, no matter the request variables, user logged in / whatever! -->
        <% include Footer %>
    <% end_cached %>


</body>
</html>

```

### Every database change invalidates cache

The cache is invalidated every time the database changes.  
To avoid this happening too often you can set a list of classes that are not included:

```yml
Sunnysideup\SimpleTemplateCaching\Extensions\DataObjectExtension:
  excluded_classes_for_caching:
    - MyClass1
    - MyClass2
```

By default a whole bunch are being excluded - see: `_config.yml`.


### exclude pages from being cached

Add the following to your Page (or Home Page or whatever) *Controller*:

```php
function canCachePage() : bool
{
     return false;
}
```

To add a specific key for template caching, you can use:

```php
function CacheKeyContentCustom() : bool
{
     return 'extra-cache-key-goes-here';
}
```
