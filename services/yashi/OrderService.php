<?php
namespace services\yashi;

class OrderService
{	
	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
	}

    public function getAllIds(): array
    {
        $orderIds = [];
        $query = 'SELECT order_id, yashi_order_id FROM zz__yashi_order';
        foreach ($this->connection->query($query) as $row) {
            $orderIds[$row['yashi_order_id']] = $row['order_id'];
        }

        return $orderIds;
    }

    /**
	 * @throws \Exception 
	 */
    public function create(int $externalId, string $orderName, int $campaignId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO zz__yashi_order(yashi_order_id, name, campaign_id)
            VALUES (:yashi_order_id, :name, :campaign_id)'
        );

        $statement->execute([
            'yashi_order_id' => $externalId,
            'name' => $orderName,
            'campaign_id' => $campaignId,
        ]);

        return $this->connection->lastInsertId();
    }

    /**
	 * @throws \Exception 
	 */
    public function insertData(
        int $orderId,
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
            'INSERT INTO zz__yashi_order_data(order_id, log_date, impression_count, click_count, 25viewed_count, 50viewed_count, 75viewed_count, 100viewed_count)
            VALUES (:order_id, :log_date, :impression_count, :click_count, :25viewed_count, :50viewed_count, :75viewed_count, :100viewed_count)
            ON DUPLICATE KEY UPDATE
            impression_count = impression_count + VALUES(impression_count),
            click_count = click_count + VALUES(click_count),
            25viewed_count = 25viewed_count + VALUES(25viewed_count),
            50viewed_count = 50viewed_count + VALUES(50viewed_count),
            75viewed_count = 75viewed_count + VALUES(75viewed_count),
            100viewed_count = 100viewed_count + VALUES(100viewed_count)'
        );

        $statement->execute([
            'order_id' => $orderId,
            'log_date' => $logDate,
            'impression_count' => $impressionCount,
            'click_count' => $clickCount,
            '25viewed_count' => $viewedCount25,
            '50viewed_count' => $viewedCount50,
            '75viewed_count' => $viewedCount75,
            '100viewed_count' => $viewedCount100,
        ]);
    }
}