<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 * @subpackage Sequences
 */

namespace Dbmover\Pgsql\Sequences;

use Dbmover\Core;
use PDO;

class Plugin extends Core\Plugin
{
    public $description = 'Checking sequences...';

    public function __invoke(string $sql) : string
    {
        $seqs = [];
        $exists = $this->loader->getPdo()->prepare("SELECT * FROM pg_class
            WHERE relkind = 'S'
            AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'public')
            AND relname = ?");
        if (preg_match_all("@^CREATE SEQUENCE(.*?);$@m", $sql, $sequences, PREG_SET_ORDER)) {
            foreach ($sequences as $sequence) {
                preg_match("@^(IF NOT EXISTS\s+)?(\w+?)$@", trim($sequence[1]), $match);
                $seqs[] = $match[2];
                $exists->execute([$match[2]]);
                if (!$exists->fetchColumn()) {
                    $this->addOperation($_sql);
                }
                $sql = str_replace($sequence[0], '', $sql);
            }
        }
        $stmt = $this->loader->getPdo()->prepare("SELECT * FROM pg_class
            WHERE relkind = 'S'
            AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'public')");
        $stmt->execute();
        while (false !== ($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (!in_array($row['relname'], $seqs)) {
                $this->defer("DROP SEQUENCE IF EXISTS {$row['relname']} CASCADE;");
            }
        }
        return $sql;
    }

    public function __destruct()
    {
        $this->description = 'Dropping deprecated sequences...';
        parent::__destruct();
    }
}

