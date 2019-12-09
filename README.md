# silverstripe-simple-template-caching
Basic Caching Functionality For Page Templates

# install

`composer require sunnysideup/simple-template-caching`

# usage

```html

<!doctype html>
<html lang="$ContentLocale">
<head>
    <title>example</title>
</head>
<body>

    <% if $HasCacheKeyHeader %>
    <% cached $CacheKeyHeader %>
        <!-- cached header -->
        <% include Header %>
    <% end_cached %>
    <% else %>
        <% include Header %>
    <% end_if %>

    <main id="main">
        <% if $HasCacheKeyContent %>
            <% cached $CacheKeyContent %>
                <!-- cached content -->
                $Layout
            <% end_cached %>
        <% else %>
            $Layout
        <% end_if %>
    </main>

    <% if $HasCacheKeyFooter %>
    <% cached $CacheKeyFooter %>
        <!-- cached footer -->
        <% include Footer %>
    <% end_cached %>
    <% else %>
        <% include Footer %>
    <% end_if %>

</body>
</html>

```
