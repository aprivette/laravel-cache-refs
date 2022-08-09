# Laravel Cache Refs

_A Laravel Artisan command to purge cache tag refs from Redis._

## Problem

Have you noticed massive Redis sets which have the key suffix `standard_ref` or `forever_ref` clogging up your cache database?

**If you use [Laravel's cache tag feature](https://laravel.com/docs/9.x/cache#cache-tags) and are unable to routinely wipe your entire cache, you will inevitably accumulate junk in your Redis database.** To make this feature work, Laravel creates Redis sets for each tag with members pointing to each key assigned to that tag.

For example, let's say you have a cache key "dog" with the tag "animal".  If the "dog" Redis key expires, that key's set member remains for the "animal" tag.

## Solution

**This command is designed to be run on a schedule job for applications experiencing this issue.** It loops through all cache refs and ensures the ref set members point to existing keys. It will remove any set members that point to non existant target keys.

## Usage

After installing, you can run the command like so:

`php artisan refs:purge`

This command defaults to the `cache` connection. If you have another cache connection, you may specify it.

`php artisan refs:purge custom_redis_connection`

## Caveats

This package only works with Redis stores.

## Future Improvements

The key queries should probably be replaced with scans for memory conservation with larger Redis stores. I haven't run into this issue yet though.
