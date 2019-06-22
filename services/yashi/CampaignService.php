<?php
namespace services\yashi;

class CampaignService
{	
	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
	}

    public function getAllIds(): array
    {
        $campaignIds = [];
        $query = 'SELECT campaign_id, yashi_campaign_id FROM zz__yashi_cgn';
        foreach ($this->connection->query($query) as $row) {
            $campaignIds[$row['yashi_campaign_id']] = $row['campaign_id'];
        }

        return $campaignIds;
    }

    /**
	 * @throws \Exception 
	 */
    public function create(int $externalId, string $campaignName, int $externalAdvertiserId, string $advertiserName): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO zz__yashi_cgn(yashi_campaign_id, name, yashi_advertiser_id, advertiser_name)
            VALUES (:yashi_campaign_id, :name, :yashi_advertiser_id, :advertiser_name)'
        );

        $statement->execute([
            'yashi_campaign_id' => $externalId,
            'name' => $campaignName,
            'yashi_advertiser_id' => $externalAdvertiserId,
            'advertiser_name' => $advertiserName,
        ]);

        return $this->connection->lastInsertId();
    }

    /**
	 * @throws \Exception 
	 */
    public function insertData(
        int $campaignId,
        int $logDate,
        int $impressionCount,
        int $clickCount,
        int $viewedCount25,
        int $viewedCount50,
        int $viewedCount75,
        int $viewedCount100
    ) {
        $statement = $this->connection->prepare(
            'INSERT INTO zz__yashi_cgn_data(campaign_id, log_date, impression_count, click_count, 25viewed_count, 50viewed_count, 75viewed_count, 100viewed_count)
            VALUES (:campaign_id, :log_date, :impression_count, :click_count, :25viewed_count, :50viewed_count, :75viewed_count, :100viewed_count)
            ON DUPLICATE KEY UPDATE
            impression_count = impression_count + VALUES(impression_count),
            click_count = click_count + VALUES(click_count),
            25viewed_count = 25viewed_count + VALUES(25viewed_count),
            50viewed_count = 50viewed_count + VALUES(50viewed_count),
            75viewed_count = 75viewed_count + VALUES(75viewed_count),
            100viewed_count = 100viewed_count + VALUES(100viewed_count)'
        );

        $statement->execute([
            'campaign_id' => $campaignId,
            'log_date' => $logDate,
            'impression_count' => $impressionCount,
            'click_count' => $clickCount,
            '25viewed_count' => $viewedCount25,
            '50viewed_count' => $viewedCount50,
            '75viewed_count' => $viewedCount75,
            '100viewed_count' => $viewedCount100,
        ]);
    }

    /**
	 * @throws \Exception 
	 */
    public function cleanDataForDay(string $day)
    {
        $statement = $this->connection->prepare(
            "DELETE FROM zz__yashi_cgn_data
            WHERE log_date = :log_date"
        );

        $statement->execute([
            'log_date' => strtotime($day)
        ]);
    }

    public function getSumsByCampaigns(): array
	{
		$statement = $this->connection->prepare(
			'SELECT
			campaign_id,
			sum(impression_count),
			sum(click_count),
			sum(25viewed_count),
			sum(50viewed_count),
			sum(75viewed_count),
			sum(100viewed_count)
			FROM
			zz__yashi_cgn_data
			GROUP BY campaign_id'
		);

		$statement->execute();
		return $statement->fetchAll();
	}
}