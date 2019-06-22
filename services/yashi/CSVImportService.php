<?php

namespace services\yashi;

class CSVImportService
{	
	public function __construct(
		\PDO $connection,
		CampaignService $campaignService,
		OrderService $orderService,
		CreativeService $creativeService
	) {
		$this->connection = $connection;
		$this->campaignService = $campaignService;
		$this->orderService = $orderService;
		$this->creativeService = $creativeService;
		$this->campaignIds = $campaignService->getAllIds();
		$this->orderIds = $orderService->getAllIds();
		$this->creativeIds = $creativeService->getAllIds();
	}
	
	/**
	 * @throws \Exception 
	 */
	public function setAdvertisersFromFile(string $fileName)
	{
		$this->advertisers = [];
		$handle = fopen($fileName,'r');

		//Ignore header line
		$data = fgetcsv($handle);

		while (($data = fgetcsv($handle)) !== FALSE) {
			$this->advertisers[$data[0]] = $data[1];
		}

		fclose($handle);
	}
	
	/**
	 * @throws \Exception 
	 */
	public function importFile(string $fileName)
	{
		$handle = fopen($fileName, 'r');

		//Using 1st line to create mapping
		$dataMapping  = array_flip(fgetcsv($handle));

		$processedCampaignIds = [];
		$processedOrderIds = [];
		$processedCreativeIds = [];

		$campaignsSums = [];
		$creativesSums = [];
		$creativeToCampaignMapping = [];

		//Using a transaction so we can rollback everything if needed
		$this->connection->beginTransaction();
		$dataCleaned = false;

		while (($data = fgetcsv($handle)) !== FALSE) {
			if (!array_key_exists($data[$dataMapping['Advertiser ID']], $this->advertisers)) {
				continue;
			}
	
			//to avoid issues if we re-run the same file
			if (!$dataCleaned) {
				$this->campaignService->cleanDataForDay($data[$dataMapping['Date']]);
				$this->orderService->cleanDataForDay($data[$dataMapping['Date']]);
				$dataCleaned = true;
			}

			try {
				$campaignId = $this->saveCampaignDataFromRow($data, $dataMapping);
				$orderId = $this->saveOrderDataFromRow($data, $dataMapping, $campaignId);
				$creativeId = $this->saveCreativeDataFromRow($data, $dataMapping, $orderId);
			} catch (\Exception $e) {
				$this->connection->rollBack();
				throw $e;
			}

			$this->campaignIds[$data[$dataMapping['Campaign ID']]] = $campaignId;
			$this->orderIds[$data[$dataMapping['Order ID']]] = $orderId;
			$this->creativeIds[$data[$dataMapping['Creative ID']]] = $creativeId;

			//the following is used to make sure the saved data make sense
			if (!in_array($campaignId, $processedCampaignIds)) {
				$processedCampaignIds[] = $campaignId;
			}
			
			if (!in_array($orderId, $processedOrderIds)) {
				$processedOrderIds[] = $orderId;
			}

			if (!in_array($creativeId, $processedCreativeIds)) {
				$processedCreativeIds[] = $creativeId;
			}
		}

		fclose($handle);
		try {
			$this->assertNumberOfEntity(count($processedCampaignIds), count($processedOrderIds), count($processedCreativeIds));
			$this->assertSums();
		} catch (\Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}

		//Everything went fine, we confirm the changes
		$this->connection->commit();
	}

	private function buildCommonDataFromRow(array $row, array $dataMapping): array
	{
		return [
			strtotime($row[$dataMapping['Date']]),
            $row[$dataMapping['Impressions']],
            $row[$dataMapping['Clicks']],
            $row[$dataMapping['25% Viewed']],
            $row[$dataMapping['50% Viewed']],
            $row[$dataMapping['75% Viewed']],
            $row[$dataMapping['100% Viewed']]
		];
	}

	/**
	 * This method allows to get the sums of data for campaign or creative data that will be used 
	 * to check if the stored data make sens as per the exercice instructions
	 */ 
	private function updateSumsFromRow(array $row, array $dataMapping, array $sums, int $entityId): array
	{
		$commonData = $this->buildCommonDataFromRow($row, $dataMapping);
		if (!isset($sums[$entityId]) || !is_array($sums[$entityId])) {
			$sums[$entityId] = [
				'impression_count' => $commonData['impression_count'],
				'click_count' => $commonData[1],
				'25viewed_count' => $commonData[2],
				'50viewed_count' => $commonData[3],
				'75viewed_count' => $commonData[4],
				'100viewed_coun' => $commonData[5],
			];
		} else {
			$sums[$entityId] = [
				'impression_count' => $sums[$entityId]['impression_count'] + $commonData['impression_count'],
				'click_count' => $sums[$entityId]['click_count'] + $commonData[1],
				'25viewed_count' => $sums[$entityId]['25viewed_count'] + $commonData[2],
				'50viewed_count' => $sums[$entityId]['50viewed_count'] + $commonData[3],
				'75viewed_count' => $sums[$entityId]['75viewed_count'] + $commonData[4],
				'100viewed_coun' => $sums[$entityId]['100viewed_count'] + $commonData[5],
			];
		}

		return $sums;
	}

	/**
	 * @throws \Exception 
	 */
	private function saveCampaignDataFromRow(array $row, array $dataMapping): int
	{
		if (!array_key_exists($row[$dataMapping['Campaign ID']], $this->campaignIds)) {
			$campaignId = $this->campaignService->create(
				$row[$dataMapping['Campaign ID']],
				$row[$dataMapping['Campaign Name']],
				$row[$dataMapping['Advertiser ID']],
				$this->advertisers[$row[$dataMapping['Advertiser ID']]]
			);
		} else {
			$campaignId = $this->campaignIds[$row[$dataMapping['Campaign ID']]];
		}


		$this->campaignService->insertData(
			$campaignId,
			...$this->buildCommonDataFromRow($row, $dataMapping)
		);

		return $campaignId;
	}

	/**
	 * @throws \Exception 
	 */
	private function saveOrderDataFromRow(array $row, array $dataMapping, int $campaignId): int
	{
		if (!array_key_exists($row[$dataMapping['Order ID']], $this->orderIds)) {
			$orderId = $this->orderService->create(
				$row[$dataMapping['Order ID']],
				$row[$dataMapping['Order Name']],
				$campaignId
			);
		} else {
			$orderId = $this->orderIds[$row[$dataMapping['Order ID']]];
		}

		$this->orderService->insertData(
			$orderId,
			...$this->buildCommonDataFromRow($row, $dataMapping)
		);

		return $orderId;
	}

	/**
	 * @throws \Exception 
	 */
	private function saveCreativeDataFromRow(array $row, array $dataMapping, int $orderId): int
	{
		if (!array_key_exists($row[$dataMapping['Creative ID']], $this->creativeIds)) {
			$creativeId = $this->creativeService->create(
				$row[$dataMapping['Creative ID']],
				$row[$dataMapping['Creative Name']],
				$row[$dataMapping['Creative Preview URL']],
				$orderId
			);
		} else {
			$creativeId = $this->creativeIds[$row[$dataMapping['Creative ID']]];
		}

		$this->creativeService->insertData(
			$creativeId,
			...$this->buildCommonDataFromRow($row, $dataMapping)
		);

		return $creativeId;
	}

	/**
	 * @throws \Exception 
	 */
	private function assertNumberOfEntity(int $campaignCount, int $orderCount, int $creativeCount)
	{
		if ($campaignCount >= $orderCount || $orderCount >= $creativeCount) {
			throw new \Exception("Entity count doesn't make sense");
		}
	}

	/**
	 * @throws \Exception 
	 */
	private function assertSums()
	{
		$campaignsSums = $this->campaignService->getSumsByCampaigns();
		$ordersSums = $this->orderService->getSumsByCampaigns();
		$creativesSums = $this->creativeService->getSumsByCampaigns();

		if ($campaignsSums !== $ordersSums || $campaignsSums !== $creativesSums) {
			throw new \Exception("Data count doesn't make sense");
		}
	}
}