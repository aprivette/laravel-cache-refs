<?php

namespace Aprivette\PurgeCacheRefs;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class PurgeCacheRefsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refs:purge {connection=cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges tagged cache refs with missing targets.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cache_prefix = Cache::getPrefix();
        $redis = Redis::connection($this->argument('connection'));

        // Select all temporary and permanent reference sets
        $standard_refs = $redis->keys("{$cache_prefix}*:standard_ref");
        $forever_refs = $redis->keys("{$cache_prefix}*:forever_ref");

        $refs = array_merge($standard_refs, $forever_refs);

        $db_prefix = config('database.redis.options.prefix');
        $ref_chunk_i = 0;
        $purged = 0;

        $this->comment('Purging broken refs');

        $this->withProgressBar($refs, function ($ref_key) use (&$ref_chunk_i, &$purged, $redis, $db_prefix) {
            /**
             * Since the Redis facade automatically applies the app's prefix,
             * we need to remove it from the key before it is queried
             */
            $ref_key = str_replace($db_prefix, '', $ref_key);
            $member_chunk_i = 0;
            $iterator = null;

            /**
             * Grab the set members in chunks, check if they exist as keys
             * in the database, and remove them from the set if they don't
             */
            do {
                $member_sscan = $redis->sScan($ref_key, $iterator);

                if (!$member_sscan) {
                    break;
                }

                $iterator = $member_sscan[0];
                $member_chunk = $member_sscan[1];

                $member_chunk_i = $member_chunk_i + count($member_chunk);

                foreach ($member_chunk as $member_key) {
                    if (!$redis->exists($member_key)) {
                        $purged++;
                        $redis->sRem($ref_key, $member_key);
                    }
                }
            } while ($iterator > 0);

            $ref_chunk_i++;
        });

        $this->newLine();
        $this->info("{$purged} broken refs purged successfully");

        return 0;
    }
}
