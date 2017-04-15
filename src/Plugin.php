<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 * @subpackage Sequences
 */

namespace Dbmover\Pgsql\Sequences;

use Dbmover\Core;

class Plugin extends Core\Plugin
{
    public $description = 'Checking sequences...';

    public function __invoke(string $sql) : string
    {
        if (preg_match_all("@^CREATE SEQUENCE(.*?);$@m", $sql, $sequences, PREG_SET_ORDER)) {
            foreach ($sequences as $sequence) {
                if (strpos($sequence[1], 'IF NOT EXISTS') === false) {
                    $_sql = "CREATE SEQUENCE IF NOT EXISTS {$sequence[1]}";
                } else {
                    $_sql = $sequence[0];
                }
                $this->addOperation($_sql);
                $sql = str_replace($sequence[0], '', $sql);
            }
        }
        // TODO: remove obsolete sequences
        return $sql;
    }
}

