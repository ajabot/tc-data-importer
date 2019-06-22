<?php
namespace services\yashi;

class CreativeService
{	
	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
	}

    public function getAllIds(): array
    {
        $creativeIds = [];
        $query = 'SELECT creative_id, yashi_creative_id FROM zz__yashi_creative';
        foreach ($this->connection->query($query) as $row) {
            $creativeIds[$row['yashi_creative_id']] = $row['creative_id'];
        }

        return $creativeIds;
    }

    /**
	 * @throws \Exception 
	 */
    public function create(int $externalId, string $creativeName, string $previewUrl, int $orderId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO zz__yashi_creative(yashi_creative_id, name, preview_url, order_id)
            VALUES (:yashi_creative_id, :name, :preview_url, :order_id)'
        );

        $statement->execute([
            'yashi_creative_id' => $externalId,
            'name' => $creativeName,
            'preview_url' => $previewUrl,
            'order_id' => $orderId,
        ]);

        return $this->connection->lastInsertId();
    }

    /**
	 * @throws \Exception 
	 */
    public function insertData(
        int $creativeId,
        int $logDate,
        int $impressionCount,
        int $clickCount,
        int $viewedCount25,
        int $viewedCount50,
        int $viewedCount75,
        int $viewedCount100
        )
    {
        $statement = $this->connection->prepare(
            'REPLACE INTO zz__yashi_creative_data(creative_id, log_date, impression_count, click_count, 25viewed_count, 50viewed_count, 75viewed_count, 100viewed_count)
            VALUES (:creative_id, :log_date, :impression_count, :click_count, :25viewed_count, :50viewed_count, :75viewed_count, :100viewed_count)'
        );

        $statement->execute([
            'creative_id' => $creativeId,
            'log_date' => $logDate,
            'impression_count' => $impressionCount,
            'click_count' => $clickCount,
            '25viewed_count' => $viewedCount25,
            '50viewed_count' => $viewedCount50,
            '75viewed_count' => $viewedCount75,
            '100viewed_count' => $viewedCount100,
        ]);
    }

    public function getSumsByCampaigns(): array
	{
		$statement = $this->connection->prepare(
			'SELECT
			zz__yashi_cgn.campaign_id,
			sum(impression_count),
			sum(click_count),
			sum(25viewed_count),
			sum(50viewed_count),
			sum(75viewed_count),
			sum(100viewed_count)
			FROM
			zz__yashi_creative_data
			INNER JOIN zz__yashi_creative ON zz__yashi_creative_data.creative_id = zz__yashi_creative.creative_id
			INNER JOIN zz__yashi_order ON zz__yashi_creative.order_id = zz__yashi_order.order_id
			INNER JOIN zz__yashi_cgn ON zz__yashi_order.campaign_id = zz__yashi_cgn.campaign_id
			GROUP BY zz__yashi_cgn.campaign_id'
		);

		$statement->execute();
		return $statement->fetchAll();
	}
}