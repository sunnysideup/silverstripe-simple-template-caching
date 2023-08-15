# silverstripe-simple-template-caching
Basic Caching Functionality For Page Templates

# make an exception
add the following to your Page (or Home Page or whatever) Controller:

```php
function canCachePage() : bool
{
     return false;
}
```

# usage
Here is how to use it in the Page.ss file (or similar):
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
            <% include Menu %>
        <% end_cached %>
    <% else %>
        <% include Menu %>
    <% end_if %>
        
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

</body>
</html>

```
