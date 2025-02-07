<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class CreateChargesTagsAggregatedViewMigration extends Migration
{
    public function up(): void
    {
        $this->database()->execute('
            CREATE OR REPLACE VIEW charges_tags_aggregated AS
            SELECT charges.id, GROUP_CONCAT(DISTINCT tag_charges.tag_id) AS tag_ids
            FROM charges
            LEFT JOIN tag_charges
	            ON charges.id = tag_charges.charge_id
            GROUP BY charges.id
        ');
    }

    public function down(): void
    {
        $this->database()->execute('DROP VIEW charges_tags_aggregated');
    }
}
